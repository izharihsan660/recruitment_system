<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\EmailIntake;
use App\Models\EmailIntakeSetting;
use App\Models\JobPosting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GraphMailIntakeService
{
    public function getAccessToken(?EmailIntakeSetting $setting = null): string
    {
        $setting ??= $this->activeSetting();
        $cacheKey = 'mail-graph-intake-token:'.$setting->id;

        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post("https://login.microsoftonline.com/{$setting->tenant_id}/oauth2/v2.0/token", [
                'client_id' => $setting->client_id,
                'client_secret' => $setting->client_secret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]);

        $this->ensureSuccessful($response, 'token');

        $token = (string) $response->json('access_token');
        $expiresIn = max(60, (int) $response->json('expires_in', 3600));

        Cache::put($cacheKey, $token, now()->addSeconds(max(1, $expiresIn - 60)));

        return $token;
    }

    /** @return Collection<int, EmailIntake> */
    public function fetchNewMessages(): Collection
    {
        $setting = $this->activeSetting();
        $token = $this->getAccessToken($setting);
        $messages = $this->fetchAllMessagePages($setting, $token)
            ->sortBy('receivedDateTime')
            ->values();
        $processed = collect();
        $lastReceivedAt = $setting->last_received_at;

        foreach ($messages as $message) {
            $processed->push($this->processMessage($setting, $token, $message));
            $receivedAt = Carbon::parse($message['receivedDateTime'])->setTimezone(config('app.timezone'));

            if ($lastReceivedAt === null || $receivedAt->isAfter($lastReceivedAt)) {
                $lastReceivedAt = $receivedAt;
            }
        }

        $setting->update([
            'last_synced_at' => now(),
            'last_received_at' => $lastReceivedAt,
        ]);

        return $processed;
    }

    public function suggestJobPosting(string $subject): ?JobPosting
    {
        $normalizedSubject = Str::of($subject)->lower()->ascii()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString();

        if ($normalizedSubject === '') {
            return null;
        }

        return JobPosting::query()
            ->where('status', 'open')
            ->orderBy('position_name')
            ->get()
            ->sortByDesc(function (JobPosting $job) use ($normalizedSubject): int {
                $position = Str::of($job->position_name)->lower()->ascii()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString();

                if ($position !== '' && str_contains($normalizedSubject, $position)) {
                    return 1000 + strlen($position);
                }

                return collect(explode(' ', $position))
                    ->filter(fn (string $word): bool => strlen($word) >= 3 && str_contains($normalizedSubject, $word))
                    ->sum(fn (string $word): int => strlen($word));
            })
            ->first(fn (JobPosting $job): bool => $this->jobMatchScore($normalizedSubject, $job->position_name) > 0);
    }

    private function activeSetting(): EmailIntakeSetting
    {
        $setting = EmailIntakeSetting::query()->where('is_active', true)->first();

        if ($setting !== null) {
            return $setting;
        }

        $configuration = config('services.mail_graph_intake');

        if (blank($configuration['tenant_id'] ?? null)
            || blank($configuration['client_id'] ?? null)
            || blank($configuration['client_secret'] ?? null)
            || blank($configuration['mailbox'] ?? null)) {
            throw new RuntimeException('Konfigurasi Microsoft Graph Email Intake belum tersedia.');
        }

        return EmailIntakeSetting::query()->create([
            'tenant_id' => $configuration['tenant_id'],
            'client_id' => $configuration['client_id'],
            'client_secret' => $configuration['client_secret'],
            'mailbox_address' => $configuration['mailbox'],
            'is_active' => true,
            'sync_interval_minutes' => 10,
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function fetchAllMessagePages(EmailIntakeSetting $setting, string $token): Collection
    {
        $url = 'https://graph.microsoft.com/v1.0/users/'.rawurlencode($setting->mailbox_address).'/messages';
        $query = [
            '$select' => 'id,subject,from,body,bodyPreview,receivedDateTime,hasAttachments',
            '$orderby' => 'receivedDateTime asc',
            '$top' => 100,
        ];

        if ($setting->last_received_at !== null) {
            $query['$filter'] = 'receivedDateTime gt '.$setting->last_received_at->utc()->format('Y-m-d\TH:i:s\Z');
        }

        $messages = collect();

        do {
            $response = Http::withToken($token)->acceptJson()->timeout(30)->get($url, $query);
            $this->ensureSuccessful($response, 'messages');
            $payload = $response->json();
            $messages->push(...($payload['value'] ?? []));
            $url = $payload['@odata.nextLink'] ?? null;
            $query = [];
        } while (filled($url));

        return $messages;
    }

    /** @param array<string, mixed> $message */
    private function processMessage(EmailIntakeSetting $setting, string $token, array $message): EmailIntake
    {
        $existing = EmailIntake::query()->where('graph_message_id', $message['id'])->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($setting, $token, $message): EmailIntake {
            $senderEmail = Str::lower((string) data_get($message, 'from.emailAddress.address'));
            $body = $this->plainTextBody($message);
            $suggestedJob = $this->suggestJobPosting((string) ($message['subject'] ?? ''));
            $isDuplicate = Candidate::query()->whereRaw('LOWER(email) = ?', [$senderEmail])->exists()
                || EmailIntake::query()->whereRaw('LOWER(sender_email) = ?', [$senderEmail])->exists();

            $intake = EmailIntake::query()->create([
                'graph_message_id' => $message['id'],
                'sender_name' => data_get($message, 'from.emailAddress.name') ?: 'Unknown',
                'sender_email' => $senderEmail,
                'subject' => $message['subject'] ?? '',
                'body' => $body,
                'phone_number' => $this->extractPhoneNumber($body),
                'received_at' => Carbon::parse($message['receivedDateTime'])->setTimezone(config('app.timezone')),
                'suggested_job_id' => $suggestedJob?->id,
                'is_duplicate' => $isDuplicate,
                'status' => 'need_review',
            ]);

            if ((bool) ($message['hasAttachments'] ?? false)) {
                $intake->update([
                    'attachment_path' => $this->storeCvAttachment($setting, $token, $message['id'], $intake->id),
                ]);
            }

            return $intake->refresh();
        });
    }

    private function storeCvAttachment(EmailIntakeSetting $setting, string $token, string $messageId, int $intakeId): ?string
    {
        $url = 'https://graph.microsoft.com/v1.0/users/'.rawurlencode($setting->mailbox_address).'/messages/'.rawurlencode($messageId).'/attachments';
        $response = Http::withToken($token)->acceptJson()->timeout(30)->get($url);
        $this->ensureSuccessful($response, 'attachments');

        $attachment = collect($response->json('value', []))->first(function (array $attachment): bool {
            $extension = Str::lower(pathinfo((string) ($attachment['name'] ?? ''), PATHINFO_EXTENSION));

            return in_array($extension, ['pdf', 'doc', 'docx'], true) && filled($attachment['contentBytes'] ?? null);
        });

        if ($attachment === null) {
            return null;
        }

        $fileName = Str::uuid().'.'.Str::lower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
        $path = "email-intake/{$intakeId}/{$fileName}";
        Storage::disk('public')->put($path, base64_decode($attachment['contentBytes'], true) ?: '');

        return $path;
    }

    /** @param array<string, mixed> $message */
    private function plainTextBody(array $message): string
    {
        $content = (string) data_get($message, 'body.content', $message['bodyPreview'] ?? '');
        $contentType = Str::lower((string) data_get($message, 'body.contentType', 'text'));

        if ($contentType === 'html') {
            $content = html_entity_decode(strip_tags(preg_replace('/<br\s*\/?\s*>/i', "\n", $content) ?? $content));
        }

        return trim(preg_replace('/\n{3,}/', "\n\n", $content) ?? $content);
    }

    private function extractPhoneNumber(string $body): ?string
    {
        preg_match('/(?<!\d)(?:\+?62|0)[\d\s().-]{8,16}\d(?!\d)/', $body, $matches);

        return isset($matches[0]) ? trim($matches[0]) : null;
    }

    private function jobMatchScore(string $normalizedSubject, string $positionName): int
    {
        $position = Str::of($positionName)->lower()->ascii()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString();

        if ($position !== '' && str_contains($normalizedSubject, $position)) {
            return 1000 + strlen($position);
        }

        return collect(explode(' ', $position))
            ->filter(fn (string $word): bool => strlen($word) >= 3 && str_contains($normalizedSubject, $word))
            ->sum(fn (string $word): int => strlen($word));
    }

    private function ensureSuccessful(Response $response, string $operation): void
    {
        if ($response->successful()) {
            return;
        }

        Log::error('Microsoft Graph email intake request gagal.', [
            'operation' => $operation,
            'status' => $response->status(),
            'response' => $this->redactedResponseBody($response),
        ]);

        throw new RuntimeException("Microsoft Graph email intake {$operation} gagal dengan status {$response->status()}.");
    }

    private function redactedResponseBody(Response $response): string
    {
        $payload = $response->json();

        if (is_array($payload)) {
            array_walk_recursive($payload, function (mixed &$value, string|int $key): void {
                if (is_string($key) && preg_match('/token|secret|password|credential/i', $key)) {
                    $value = '[REDACTED]';
                }
            });

            return Str::limit((string) json_encode($payload), 2000);
        }

        return Str::limit(preg_replace('/(access_token|client_secret|refresh_token)\s*[=:]\s*[^\s,}]+/i', '$1=[REDACTED]', $response->body()) ?? '', 2000);
    }
}
