<?php

namespace Tests\Unit;

use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\GraphMailSenderSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MicrosoftGraphTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_it_sends_graph_payload_and_reuses_cached_sender_token(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'sender-access-token',
                'expires_in' => 3600,
            ]),
            'graph.microsoft.com/*' => Http::response(status: 202),
        ]);

        $transport = new MicrosoftGraphTransport($this->setting());
        $email = (new Email)
            ->from('no-reply@nusantaraabadijaya.com')
            ->to('recipient@example.com')
            ->cc('copy@example.com')
            ->bcc('hidden@example.com')
            ->subject('Status Rekrutmen')
            ->html('<p>Lamaran diperbarui.</p>')
            ->attach('attachment-content', 'status.txt', 'text/plain');

        $transport->send($email);
        $transport->send($email);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/oauth2/v2.0/token')
            && $request['client_id'] === 'sender-client-id'
            && $request['client_secret'] === 'sender-client-secret');
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/users/no-reply%40nusantaraabadijaya.com/sendMail')
            && $request->hasHeader('Authorization', 'Bearer sender-access-token')
            && $request['message']['subject'] === 'Status Rekrutmen'
            && $request['message']['body']['contentType'] === 'HTML'
            && $request['message']['toRecipients'][0]['emailAddress']['address'] === 'recipient@example.com'
            && $request['message']['ccRecipients'][0]['emailAddress']['address'] === 'copy@example.com'
            && $request['message']['bccRecipients'][0]['emailAddress']['address'] === 'hidden@example.com'
            && $request['message']['attachments'][0]['contentBytes'] === base64_encode('attachment-content'));
        $this->assertSame('sender-access-token', Cache::get('mail-graph-sender-token:'.hash('sha256', 'no-reply@nusantaraabadijaya.com')));
        $this->assertNull(Cache::get('mail-graph-intake-token:1'));
    }

    public function test_it_logs_safe_graph_error_context_and_throws_transport_exception(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'sender-access-token',
                'expires_in' => 3600,
            ]),
            'graph.microsoft.com/*' => Http::response(['error' => ['code' => 'ErrorAccessDenied']], 403),
        ]);

        Log::shouldReceive('error')->once()->with(
            'Microsoft Graph mail sender request failed.',
            [
                'operation' => 'sendMail',
                'status' => 403,
                'response_body' => '{"error":{"code":"ErrorAccessDenied"}}',
                'sender' => 'no-reply@nusantaraabadijaya.com',
            ],
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Microsoft Graph sendMail gagal dengan status 403.');

        (new MicrosoftGraphTransport($this->setting()))->send(
            (new Email)->from('no-reply@nusantaraabadijaya.com')->to('recipient@example.com')->subject('Test')->text('Body'),
        );
    }

    private function setting(): GraphMailSenderSetting
    {
        return new GraphMailSenderSetting([
            'tenant_id' => 'sender-tenant-id',
            'client_id' => 'sender-client-id',
            'client_secret' => 'sender-client-secret',
            'sender_mailbox' => 'no-reply@nusantaraabadijaya.com',
            'from_name' => 'Sistem Rekrutmen Nusantara Abadi Jaya',
            'is_active' => true,
        ]);
    }
}
