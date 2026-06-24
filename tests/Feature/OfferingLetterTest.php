<?php

namespace Tests\Feature;

use App\Jobs\ArchiveDocumentToSharePoint;
use App\Models\Application;
use App\Models\BackgroundCheck;
use App\Models\CompanySigner;
use App\Models\User;
use App\Services\DocuSealService;
use App\Services\OfferingLetterService;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class OfferingLetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_offering_from_clear_application_creates_draft(): void
    {
        [$application, $hr] = $this->offeringReadyApplication();

        app(OfferingLetterService::class)->create($application, $this->payload(), $hr);

        $this->assertDatabaseHas('offering_letters', ['application_id' => $application->id, 'status' => 'draft']);
    }

    public function test_send_offering_creates_docuseal_submission(): void
    {
        [$application, $hr] = $this->offeringReadyApplication();
        $offering = app(OfferingLetterService::class)->create($application, $this->payload(), $hr);
        $this->mockDocuSeal();

        app(OfferingLetterService::class)->send($offering, $hr);

        $this->assertDatabaseHas('offering_letters', ['id' => $offering->id, 'docuseal_submission_id' => 'sub_123', 'status' => 'sent']);
    }

    public function test_webhook_signed_sets_signed_and_dispatches_archive(): void
    {
        Bus::fake();
        [$application, $hr] = $this->offeringReadyApplication(['status' => 'offering']);
        $offering = app(OfferingLetterService::class)->create($application, $this->payload(), $hr);
        $offering->update(['status' => 'sent', 'docuseal_submission_id' => 'sub_123']);
        $this->mockDocuSeal(download: true);

        app(OfferingLetterService::class)->handleWebhook(['event' => 'submission.completed', 'metadata' => ['offering_letter_id' => $offering->id]]);

        $this->assertDatabaseHas('offering_letters', ['id' => $offering->id, 'status' => 'signed']);
        Bus::assertDispatched(ArchiveDocumentToSharePoint::class);
    }

    public function test_webhook_expired_sets_status_expired(): void
    {
        [$application, $hr] = $this->offeringReadyApplication();
        $offering = app(OfferingLetterService::class)->create($application, $this->payload(), $hr);

        app(OfferingLetterService::class)->handleWebhook(['event' => 'submission.expired', 'metadata' => ['offering_letter_id' => $offering->id]]);

        $this->assertDatabaseHas('offering_letters', ['id' => $offering->id, 'status' => 'expired']);
    }

    private function offeringReadyApplication(array $state = []): array
    {
        $this->seed(RolePermissionSeeder::class);
        $hr = User::factory()->create(['is_active' => true]);
        $hr->assignRole(Roles::HrRecruiter);
        $application = Application::factory()->create(array_merge(['status' => 'offering'], $state));
        BackgroundCheck::query()->create(['application_id' => $application->id, 'decision' => 'clear', 'verified_by' => $hr->id, 'verified_at' => now()]);
        CompanySigner::factory()->create(['entity_id' => $application->jobPosting->entity_id, 'user_id' => $hr->id, 'document_type' => 'offering']);

        return [$application, $hr];
    }

    private function payload(): array
    {
        return ['start_date' => now()->addWeek()->toDateString(), 'contract_duration' => '12 bulan', 'salary_gross' => 7000000, 'salary_nett' => 6500000, 'allowances' => ['transport' => 500000], 'expiry_date' => now()->addWeeks(2)->toDateString()];
    }

    private function mockDocuSeal(bool $download = false): void
    {
        $mock = Mockery::mock(DocuSealService::class);
        $mock->shouldReceive('createSubmission')->andReturn(['id' => 'sub_123', 'submitters' => [['role' => 'HR Signer', 'signing_url' => 'https://sign/hr'], ['role' => 'Candidate', 'signing_url' => 'https://sign/candidate']]]);
        $mock->shouldReceive('downloadSignedDocument')->andReturn('%PDF-1.4 signed');
        $mock->shouldReceive('compressPdf')->andReturn(false);
        $this->app->instance(DocuSealService::class, $mock);
    }
}
