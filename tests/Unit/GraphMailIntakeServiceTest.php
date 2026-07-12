<?php

namespace Tests\Unit;

use App\Http\Resources\EmailIntakeResource;
use App\Models\EmailIntake;
use App\Models\EmailIntakeSetting;
use App\Models\JobPosting;
use App\Services\GraphMailIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GraphMailIntakeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_access_token_is_cached_until_before_expiry(): void
    {
        $setting = EmailIntakeSetting::factory()->create();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'cached-token',
                'expires_in' => 3600,
            ]),
        ]);

        $service = app(GraphMailIntakeService::class);

        $this->assertSame('cached-token', $service->getAccessToken($setting));
        $this->assertSame('cached-token', $service->getAccessToken($setting));
        Http::assertSentCount(1);
    }

    public function test_fetch_follows_pagination_stores_attachment_and_updates_watermark_after_batch(): void
    {
        Storage::fake('public');
        $setting = EmailIntakeSetting::factory()->create([
            'last_received_at' => '2026-07-01 00:00:00',
        ]);
        JobPosting::factory()->open()->create(['position_name' => 'Software Engineer']);

        $messagePage = 0;

        Http::fake(function (Request $request) use (&$messagePage) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'graph-token', 'expires_in' => 3600]);
            }

            if (str_contains($request->url(), '/messages/message-1/attachments')) {
                return Http::response(['value' => [[
                    'name' => 'CV Kandidat.pdf',
                    'contentBytes' => base64_encode('pdf-content'),
                ]]]);
            }

            $messagePage++;

            if ($messagePage === 2) {
                return Http::response(['value' => [[
                    'id' => 'message-2',
                    'subject' => 'Lamaran Software Engineer',
                    'from' => ['emailAddress' => ['name' => 'Pelamar Dua', 'address' => 'same@example.test']],
                    'body' => ['contentType' => 'text', 'content' => 'Nomor HP 0812 3456 7890'],
                    'receivedDateTime' => '2026-07-02T09:00:00Z',
                    'hasAttachments' => false,
                ]]]);
            }

            return Http::response([
                'value' => [[
                    'id' => 'message-1',
                    'subject' => 'CV Software Engineer',
                    'from' => ['emailAddress' => ['name' => 'Pelamar Satu', 'address' => 'same@example.test']],
                    'body' => ['contentType' => 'html', 'content' => '<p>Lamaran saya</p>'],
                    'receivedDateTime' => '2026-07-02T08:00:00Z',
                    'hasAttachments' => true,
                ]],
                '@odata.nextLink' => 'https://graph.microsoft.com/v1.0/users/mailbox/messages?page=2',
            ]);
        });

        $emails = app(GraphMailIntakeService::class)->fetchNewMessages();

        $this->assertCount(2, $emails);
        $this->assertDatabaseCount('email_intakes', 2);
        $first = EmailIntake::query()->where('graph_message_id', 'message-1')->firstOrFail();
        $second = EmailIntake::query()->where('graph_message_id', 'message-2')->firstOrFail();
        $this->assertSame('need_review', $first->status);
        $this->assertSame('need_review', $second->status);
        $this->assertFalse($first->is_duplicate);
        $this->assertTrue($second->is_duplicate);
        $this->assertSame('0812 3456 7890', $second->phone_number);
        $this->assertNotNull($first->suggested_job_id);
        Storage::disk('public')->assertExists($first->attachment_path);
        $resource = (new EmailIntakeResource($first))->resolve();
        $this->assertStringContainsString('/storage/email-intake/', $resource['attachment_url']);
        $this->assertSame('2026-07-02 09:00:00', $setting->refresh()->last_received_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertNotNull($setting->last_synced_at);
        $this->assertSame(2, $messagePage);
    }

    public function test_graph_message_id_is_idempotent_even_when_graph_returns_same_message_again(): void
    {
        EmailIntakeSetting::factory()->create();
        EmailIntake::factory()->create(['graph_message_id' => 'existing-message']);

        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'graph-token', 'expires_in' => 3600]),
            'https://graph.microsoft.com/*' => Http::response(['value' => [[
                'id' => 'existing-message',
                'subject' => 'Lamaran',
                'from' => ['emailAddress' => ['name' => 'Existing', 'address' => 'existing@example.test']],
                'bodyPreview' => 'Body',
                'receivedDateTime' => '2026-07-02T08:00:00Z',
                'hasAttachments' => false,
            ]]]),
        ]);

        app(GraphMailIntakeService::class)->fetchNewMessages();

        $this->assertDatabaseCount('email_intakes', 1);
    }

    public function test_subject_suggestion_only_matches_open_job_posting(): void
    {
        $open = JobPosting::factory()->open()->create(['position_name' => 'Finance Staff']);
        JobPosting::factory()->create(['position_name' => 'Software Engineer', 'status' => 'closed']);

        $suggestion = app(GraphMailIntakeService::class)->suggestJobPosting('Lamaran Finance Staff berpengalaman');

        $this->assertTrue($suggestion?->is($open));
    }

    public function test_failed_fetch_does_not_crash_scheduled_command_or_log_sensitive_values(): void
    {
        EmailIntakeSetting::factory()->create(['client_secret' => 'never-log-this-secret']);
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_client',
                'access_token' => 'never-log-this-token',
                'client_secret' => 'never-log-this-secret',
            ], 401),
        ]);

        $this->artisan('email-intake:fetch')->assertExitCode(0);

        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context): bool {
            $serialized = json_encode($context);

            return ! str_contains($serialized, 'never-log-this-token')
                && ! str_contains($serialized, 'never-log-this-secret');
        })->atLeast()->once();
    }
}
