<?php

namespace App\Mail\Transport;

use App\Models\GraphMailSenderSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(private readonly ?GraphMailSenderSetting $setting = null)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'graph';
    }

    protected function doSend(SentMessage $message): void
    {
        $setting = $this->setting ?? $this->activeSetting();
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $response = Http::withToken($this->accessToken($setting))
            ->acceptJson()
            ->timeout(30)
            ->post(
                'https://graph.microsoft.com/v1.0/users/'.rawurlencode($setting->sender_mailbox).'/sendMail',
                $this->payload($email, $setting),
            );

        if ($response->failed()) {
            $this->throwTransportException($response, $setting->sender_mailbox, 'sendMail');
        }
    }

    private function accessToken(GraphMailSenderSetting $setting): string
    {
        $cacheKey = 'mail-graph-sender-token:'.($setting->getKey() ?? hash('sha256', $setting->sender_mailbox));
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

        if ($response->failed()) {
            $this->throwTransportException($response, $setting->sender_mailbox, 'token');
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new TransportException('Microsoft Graph token response tidak memiliki access_token.');
        }

        $expiresIn = max(60, (int) $response->json('expires_in', 3600));
        Cache::put($cacheKey, $token, now()->addSeconds(max(1, $expiresIn - 60)));

        return $token;
    }

    /** @return array<string, mixed> */
    private function payload(Email $email, GraphMailSenderSetting $setting): array
    {
        $htmlBody = $email->getHtmlBody();
        $message = [
            'subject' => $email->getSubject() ?? '',
            'body' => [
                'contentType' => $htmlBody !== null ? 'HTML' : 'Text',
                'content' => $htmlBody ?? $email->getTextBody() ?? '',
            ],
            'from' => [
                'emailAddress' => [
                    'address' => $setting->sender_mailbox,
                    'name' => $setting->from_name,
                ],
            ],
            'toRecipients' => $this->recipients($email->getTo()),
            'ccRecipients' => $this->recipients($email->getCc()),
            'bccRecipients' => $this->recipients($email->getBcc()),
        ];

        $attachments = array_map(fn (DataPart $attachment): array => $this->attachment($attachment), $email->getAttachments());

        if ($attachments !== []) {
            $message['attachments'] = $attachments;
        }

        return ['message' => $message, 'saveToSentItems' => true];
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array{emailAddress: array{address: string, name: string}}>
     */
    private function recipients(array $addresses): array
    {
        return array_map(static fn (Address $address): array => [
            'emailAddress' => [
                'address' => $address->getAddress(),
                'name' => $address->getName(),
            ],
        ], $addresses);
    }

    /** @return array<string, mixed> */
    private function attachment(DataPart $attachment): array
    {
        $payload = [
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'name' => $attachment->getFilename() ?? 'attachment',
            'contentType' => $attachment->getContentType(),
            'contentBytes' => base64_encode($attachment->getBody()),
        ];

        if ($attachment->getDisposition() === 'inline') {
            $payload['isInline'] = true;
            $payload['contentId'] = $attachment->getContentId();
        }

        return $payload;
    }

    private function activeSetting(): GraphMailSenderSetting
    {
        $setting = GraphMailSenderSetting::query()->where('is_active', true)->first();

        if ($setting !== null) {
            return $setting;
        }

        $configuration = config('services.mail_graph_sender');

        if (blank($configuration['tenant_id'] ?? null)
            || blank($configuration['client_id'] ?? null)
            || blank($configuration['client_secret'] ?? null)
            || blank($configuration['mailbox'] ?? null)) {
            throw new RuntimeException('Konfigurasi Microsoft Graph Mail Sender belum tersedia.');
        }

        return new GraphMailSenderSetting([
            'tenant_id' => $configuration['tenant_id'],
            'client_id' => $configuration['client_id'],
            'client_secret' => $configuration['client_secret'],
            'sender_mailbox' => $configuration['mailbox'],
            'from_name' => $configuration['from_name'] ?? config('mail.from.name'),
            'is_active' => true,
        ]);
    }

    private function throwTransportException(Response $response, string $sender, string $operation): never
    {
        Log::error('Microsoft Graph mail sender request failed.', [
            'operation' => $operation,
            'status' => $response->status(),
            'response_body' => $response->body(),
            'sender' => $sender,
        ]);

        throw new TransportException("Microsoft Graph {$operation} gagal dengan status {$response->status()}.");
    }
}
