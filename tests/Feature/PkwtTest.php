<?php

namespace Tests\Feature;

use App\Jobs\ArchiveDocumentToSharePoint;
use App\Models\Application;
use App\Models\CompanySigner;
use App\Models\OfferingLetter;
use App\Models\User;
use App\Services\DocuSealService;
use App\Services\PkwtService;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class PkwtTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pkwt_from_approved_hiring_decision_creates_draft(): void
    {
        [$application, $hr] = $this->pkwtReadyApplication();

        app(PkwtService::class)->create($application, $hr);

        $this->assertDatabaseHas('pkwt_contracts', ['application_id' => $application->id, 'status' => 'draft']);
    }

    public function test_send_pkwt_creates_docuseal_submission(): void
    {
        [$application, $hr] = $this->pkwtReadyApplication();
        $pkwt = app(PkwtService::class)->create($application, $hr);
        $this->mockDocuSeal();

        app(PkwtService::class)->send($pkwt, $hr);

        $this->assertDatabaseHas('pkwt_contracts', ['id' => $pkwt->id, 'docuseal_submission_id' => 'sub_pkwt', 'status' => 'sent']);
    }

    public function test_webhook_completed_sets_signed_hired_and_dispatches_archive(): void
    {
        Bus::fake();
        [$application, $hr] = $this->pkwtReadyApplication();
        $pkwt = app(PkwtService::class)->create($application, $hr);
        $pkwt->update(['status' => 'sent', 'docuseal_submission_id' => 'sub_pkwt']);
        $this->mockDocuSeal(download: true);

        app(PkwtService::class)->handleWebhook(['event' => 'submission.completed', 'metadata' => ['pkwt_contract_id' => $pkwt->id]]);

        $this->assertDatabaseHas('pkwt_contracts', ['id' => $pkwt->id, 'status' => 'signed']);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'hired']);
        Bus::assertDispatched(ArchiveDocumentToSharePoint::class);
    }

    private function pkwtReadyApplication(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $hr = User::factory()->create(['is_active' => true]);
        $hr->assignRole(Roles::HrRecruiter);
        $application = Application::factory()->create(['status' => 'pkwt']);
        CompanySigner::factory()->create(['entity_id' => $application->jobPosting->entity_id, 'user_id' => $hr->id, 'document_type' => 'pkwt']);
        OfferingLetter::query()->create([
            'application_id' => $application->id,
            'entity_id' => $application->jobPosting->entity_id,
            'hr_signer_id' => $hr->id,
            'position_name' => $application->jobPosting->position_name,
            'department' => $application->jobPosting->department->name,
            'work_location' => $application->jobPosting->work_location,
            'contract_type' => $application->jobPosting->employment_status,
            'start_date' => now()->addWeek()->toDateString(),
            'contract_duration' => '12 bulan',
            'salary_gross' => 7000000,
            'salary_nett' => 6500000,
            'allowances' => ['transport' => 500000],
            'expiry_date' => now()->addWeeks(2)->toDateString(),
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        return [$application, $hr];
    }

    private function mockDocuSeal(bool $download = false): void
    {
        $mock = Mockery::mock(DocuSealService::class);
        $mock->shouldReceive('createSubmission')->andReturn(['id' => 'sub_pkwt', 'submitters' => [['role' => 'Candidate', 'signing_url' => 'https://sign/candidate'], ['role' => 'Company Signer', 'signing_url' => 'https://sign/company']]]);
        $mock->shouldReceive('downloadSignedDocument')->andReturn('%PDF-1.4 signed');
        $mock->shouldReceive('compressPdf')->andReturn(false);
        $this->app->instance(DocuSealService::class, $mock);
    }
}
