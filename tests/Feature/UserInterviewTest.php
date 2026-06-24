<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Models\UserInterview;
use App\Notifications\UserInterviewScheduledNotification;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserInterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_interview_sends_email_notification(): void
    {
        Notification::fake();
        $hr = $this->hrUser();
        $hiringManager = $this->hiringManager();
        $application = Application::factory()->create(['status' => 'interview_user']);

        $this->actingAs($hr)->postJson("/hr/interview-user/{$application->id}/schedule", [
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'location' => 'Ruang Meeting A',
            'interviewer_id' => $hiringManager->id,
        ])->assertRedirect();

        Notification::assertSentTo($hiringManager, UserInterviewScheduledNotification::class);
        Notification::assertSentTo($application->candidate, UserInterviewScheduledNotification::class);
    }

    public function test_hiring_manager_submit_scorecard_accepted_moves_application_to_next_stage(): void
    {
        $hr = $this->hrUser();
        $hiringManager = $this->hiringManager();
        $application = Application::factory()->create(['status' => 'interview_user']);
        UserInterview::factory()->create(['application_id' => $application->id, 'interviewer_id' => $hiringManager->id]);

        $this->actingAs($hiringManager)->postJson("/hr/interview-user/{$application->id}/scorecard", $this->payload())->assertRedirect();

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'background_check']);
    }

    public function test_submit_scorecard_rejected_without_reason_is_rejected_by_validation(): void
    {
        $hiringManager = $this->hiringManager();
        $application = Application::factory()->create(['status' => 'interview_user']);
        UserInterview::factory()->create(['application_id' => $application->id, 'interviewer_id' => $hiringManager->id]);

        $this->actingAs($hiringManager)->postJson("/hr/interview-user/{$application->id}/scorecard", $this->payload([
            'recommendation' => 'rejected',
            'rejection_reason' => '',
        ]))->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');
    }

    public function test_hr_cannot_submit_user_interview_scorecard(): void
    {
        $hr = $this->hrUser();
        $hiringManager = $this->hiringManager();
        $application = Application::factory()->create(['status' => 'interview_user']);
        UserInterview::factory()->create(['application_id' => $application->id, 'interviewer_id' => $hiringManager->id]);

        $this->actingAs($hr)->postJson("/hr/interview-user/{$application->id}/scorecard", $this->payload())->assertForbidden();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'score_technical' => 4,
            'score_experience' => 4,
            'score_problem_solving' => 4,
            'score_team_fit' => 4,
            'recommendation' => 'accepted',
            'rejection_reason' => null,
            'notes' => 'Cocok dengan kebutuhan user.',
        ], $overrides);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }

    private function hiringManager(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HiringManager);

        return $user;
    }
}
