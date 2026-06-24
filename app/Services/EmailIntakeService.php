<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\EmailIntake;
use App\Models\GraphApiConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EmailIntakeService
{
    public function fetchEmails(): Collection
    {
        $config = GraphApiConfig::query()->where('is_active', true)->first();

        if ($config === null || blank($config->recruitment_mailbox)) {
            return collect();
        }

        $token = $this->getAccessToken($config);
        $response = Http::withToken($token)
            ->timeout(30)
            ->get("https://graph.microsoft.com/v1.0/users/{$config->recruitment_mailbox}/messages", [
                'filter' => 'isRead eq false',
                'top' => 50,
            ])
            ->throw();

        $emails = collect($response->json('value', []));

        return $emails->map(fn (array $emailData) => $this->processEmail($emailData, $config->recruitment_mailbox));
    }

    public function processEmail(array $emailData, ?string $mailbox = null): EmailIntake
    {
        return DB::transaction(function () use ($emailData): EmailIntake {
            $isDuplicate = Candidate::query()->where('email', $emailData['from']['emailAddress']['address'])->exists();

            return EmailIntake::query()->firstOrCreate(
                ['graph_message_id' => $emailData['id']],
                [
                    'sender_name' => $emailData['from']['emailAddress']['name'] ?? 'Unknown',
                    'sender_email' => $emailData['from']['emailAddress']['address'],
                    'subject' => $emailData['subject'] ?? '',
                    'body' => $emailData['bodyPreview'] ?? '',
                    'received_at' => $emailData['receivedDateTime'],
                    'is_duplicate' => $isDuplicate,
                    'status' => 'need_review',
                ]
            );
        });
    }

    private function getAccessToken(GraphApiConfig $config): string
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post("https://login.microsoftonline.com/{$config->tenant_id}/oauth2/v2.0/token", [
                'client_id' => $config->client_id,
                'client_secret' => $config->client_secret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])
            ->throw();

        return $response->json('access_token');
    }
}
