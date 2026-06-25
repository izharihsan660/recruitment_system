<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\TalentPool;
use App\Models\User;
use App\Services\PipelineService;
use App\Services\TalentPoolService;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_rejected_from_pipeline_is_added_to_talent_pool_when_has_consent(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'interview_hr', 'consent' => true]);

        app(PipelineService::class)->reject($application, $hr, 'Tidak sesuai kebutuhan', false);

        $this->assertDatabaseHas('talent_pools', ['candidate_id' => $application->candidate_id]);
    }

    public function test_candidate_rejected_with_skip_talent_pool_override_is_not_added(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'screening', 'consent' => true]);

        app(PipelineService::class)->reject($application, $hr, 'Override recruiter', true);

        $this->assertDatabaseMissing('talent_pools', ['candidate_id' => $application->candidate_id]);
    }

    public function test_do_not_contact_candidate_cannot_be_assigned_to_job(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->open()->create();
        $talentPool = TalentPool::factory()->create(['status' => 'do_not_contact']);

        $this->actingAs($hr)->postJson("/hr/talent-pool/{$talentPool->id}/assign-to-job", [
            'job_posting_id' => $job->id,
        ])->assertUnprocessable();
    }

    public function test_assign_talent_pool_to_job_creates_new_application(): void
    {
        $hr = $this->hrUser();
        $candidate = Candidate::factory()->create();
        $job = JobPosting::factory()->open()->create();
        $talentPool = app(TalentPoolService::class)->addManual($candidate, ['status' => 'active'], $hr);

        $this->actingAs($hr)->post("/hr/talent-pool/{$talentPool->id}/assign-to-job", [
            'job_posting_id' => $job->id,
        ])->assertRedirect()->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $this->assertDatabaseHas('applications', [
            'job_posting_id' => $job->id,
            'candidate_id' => $candidate->id,
            'source' => 'talent_pool',
            'status' => 'applied',
        ]);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
