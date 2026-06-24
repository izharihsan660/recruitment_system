<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\GraphApiConfig;
use App\Models\HrInterview;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HrInterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_interview_generates_teams_link(): void
    {
        $hr = $this->hrUser();
        GraphApiConfig::factory()->create(['is_active' => true, 'calendar_user_email' => 'calendar@example.test']);
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
            'graph.microsoft.com/*' => Http::response(['id' => 'meeting-123', 'joinWebUrl' => 'https://teams.example.test/join']),
        ]);
        $application = Application::factory()->create(['status' => 'interview_hr']);

        $this->actingAs($hr)->postJson("/hr/interview-hr/{$application->id}/schedule", [
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'interviewer_id' => $hr->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('hr_interviews', [
            'application_id' => $application->id,
            'teams_meeting_link' => 'https://teams.example.test/join',
            'teams_meeting_id' => 'meeting-123',
        ]);
    }

    public function test_submit_scorecard_recommended_moves_application_to_next_stage(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'interview_hr']);
        HrInterview::factory()->create(['application_id' => $application->id, 'interviewer_id' => $hr->id]);

        $this->actingAs($hr)->postJson("/hr/interview-hr/{$application->id}/scorecard", $this->payload())->assertRedirect();

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'interview_user']);
    }

    public function test_submit_scorecard_not_recommended_without_notes_is_rejected_by_validation(): void
    {
        $hr = $this->hrUser();
        $application = Application::factory()->create(['status' => 'interview_hr']);
        HrInterview::factory()->create(['application_id' => $application->id, 'interviewer_id' => $hr->id]);

        $this->actingAs($hr)->postJson("/hr/interview-hr/{$application->id}/scorecard", $this->payload([
            'recommendation' => 'not_recommended',
            'notes' => '',
        ]))->assertUnprocessable()->assertJsonValidationErrors('notes');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'score_communication' => 4,
            'score_personality' => 4,
            'score_motivation' => 4,
            'score_attitude' => 4,
            'score_culture_fit' => 4,
            'strengths' => 'Komunikasi baik',
            'weaknesses' => 'Perlu adaptasi',
            'salary_expectation' => 7000000,
            'recommendation' => 'recommended',
            'notes' => 'Direkomendasikan lanjut.',
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
