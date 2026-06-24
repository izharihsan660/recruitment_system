<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_screening_passed_moves_application_to_next_stage(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->open()->create(['test_required' => true]);
        $application = Application::factory()->for($job)->create(['status' => 'screening']);

        $this->actingAs($hr)->postJson("/hr/screening/{$application->id}", $this->payload(['decision' => 'passed']))->assertRedirect();

        $this->assertDatabaseHas('screenings', ['application_id' => $application->id, 'decision' => 'passed']);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'test_psikotes']);
    }

    public function test_submit_screening_failed_without_reason_is_rejected_by_validation(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'screening']);

        $this->actingAs($hr)->postJson("/hr/screening/{$application->id}", $this->payload([
            'decision' => 'failed',
            'rejection_reason' => '',
        ]))->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');
    }

    public function test_submit_screening_failed_with_reason_rejects_application(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'screening']);

        $this->actingAs($hr)->postJson("/hr/screening/{$application->id}", $this->payload([
            'decision' => 'failed',
            'rejection_reason' => 'Dokumen tidak lengkap',
        ]))->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'rejection_stage' => 'screening',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'education_match' => true,
            'experience_match' => true,
            'document_complete' => true,
            'notes' => 'Sesuai kualifikasi awal.',
            'decision' => 'passed',
            'rejection_reason' => null,
        ], $overrides);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
