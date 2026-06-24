<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\HrInterview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrInterview>
 */
class HrInterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->state(['status' => 'interview_hr']),
            'scheduled_at' => now()->addDay(),
            'teams_meeting_link' => null,
            'teams_meeting_id' => null,
            'interviewer_id' => User::factory()->state(['is_active' => true]),
            'status' => 'scheduled',
        ];
    }
}
