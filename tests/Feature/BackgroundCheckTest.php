<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackgroundCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_clear_moves_application_to_offering(): void
    {
        $application = Application::factory()->create(['status' => 'background_check']);

        $this->actingAs($this->hrUser())->postJson("/hr/background-check/{$application->id}", $this->payload(['decision' => 'clear']))->assertRedirect();

        $this->assertDatabaseHas('background_checks', ['application_id' => $application->id, 'decision' => 'clear']);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'offering']);
    }

    public function test_submit_failed_without_reason_is_rejected_by_validation(): void
    {
        $application = Application::factory()->create(['status' => 'background_check']);

        $this->actingAs($this->hrUser())->postJson("/hr/background-check/{$application->id}", $this->payload([
            'decision' => 'failed',
            'rejection_reason' => '',
        ]))->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');
    }

    public function test_submit_issue_keeps_application_in_background_check(): void
    {
        $application = Application::factory()->create(['status' => 'background_check']);

        $this->actingAs($this->hrUser())->postJson("/hr/background-check/{$application->id}", $this->payload(['decision' => 'issue']))->assertRedirect();

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'background_check']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'ktp_verified' => true,
            'ijazah_verified' => true,
            'certificate_verified' => true,
            'reference_verified' => null,
            'notes' => 'Dokumen sesuai.',
            'decision' => 'clear',
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
