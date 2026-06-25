<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\Screening;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_move_stage_sequentially(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'applied']);

        $this->actingAs($hr)->post("/hr/pipeline/{$application->id}/move")->assertRedirect()->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'screening']);
        $this->assertDatabaseHas('pipeline_logs', ['application_id' => $application->id, 'from_stage' => 'applied', 'to_stage' => 'screening']);
    }

    public function test_candidate_cannot_skip_stage_directly_applied_to_interview_hr(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'applied']);

        $this->actingAs($hr)->post("/hr/pipeline/{$application->id}/move", [
            'to_stage' => 'interview_hr',
        ])->assertRedirect()->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'screening']);
        $this->assertDatabaseMissing('pipeline_logs', ['application_id' => $application->id, 'from_stage' => 'applied', 'to_stage' => 'interview_hr']);
    }

    public function test_test_psikotes_is_skipped_when_job_does_not_require_test(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->open()->create(['test_required' => false]);
        $application = Application::factory()->for($job)->create(['status' => 'screening']);
        $this->createScreening($application, $hr, 'passed');

        $this->actingAs($hr)->post("/hr/pipeline/{$application->id}/move")->assertRedirect()->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'interview_hr']);
        $this->assertDatabaseHas('pipeline_logs', ['application_id' => $application->id, 'to_stage' => 'interview_hr']);
    }

    public function test_candidate_cannot_move_from_screening_before_screening_form_is_completed(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'screening']);

        $this->actingAs($hr)->from('/hr/pipeline')->post("/hr/pipeline/{$application->id}/move")
            ->assertRedirect('/hr/pipeline')
            ->assertSessionHasErrors(['status' => 'Form screening harus diisi dan keputusan screening harus final sebelum kandidat dipindahkan.']);

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'screening']);
        $this->assertDatabaseMissing('pipeline_logs', ['application_id' => $application->id, 'from_stage' => 'screening']);
    }

    public function test_candidate_cannot_move_from_failed_screening_and_must_be_rejected(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'screening']);
        $this->createScreening($application, $hr, 'failed');

        $this->actingAs($hr)->from('/hr/pipeline')->post("/hr/pipeline/{$application->id}/move")
            ->assertRedirect('/hr/pipeline')
            ->assertSessionHasErrors(['status' => 'Kandidat gagal screening, gunakan Reject.']);

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'screening']);
        $this->assertDatabaseMissing('pipeline_logs', ['application_id' => $application->id, 'from_stage' => 'screening']);
    }

    public function test_reject_requires_reason_and_adds_to_talent_pool_when_consent_true(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'interview_user', 'consent' => true]);

        $this->actingAs($hr)->postJson("/hr/pipeline/{$application->id}/reject", [
            'reason' => '',
        ])->assertUnprocessable();

        $this->actingAs($hr)->post("/hr/pipeline/{$application->id}/reject", [
            'reason' => 'Tidak cocok budaya kerja',
        ])->assertRedirect()->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'rejection_stage' => 'interview_user',
        ]);
        $this->assertDatabaseHas('talent_pools', ['candidate_id' => $application->candidate_id]);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }

    private function createScreening(Application $application, User $hr, string $decision): Screening
    {
        return Screening::query()->create([
            'application_id' => $application->id,
            'education_match' => true,
            'experience_match' => true,
            'document_complete' => true,
            'decision' => $decision,
            'reviewed_by' => $hr->id,
            'reviewed_at' => now(),
        ]);
    }
}
