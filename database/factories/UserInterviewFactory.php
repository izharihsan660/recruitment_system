<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\User;
use App\Models\UserInterview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserInterview>
 */
class UserInterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->state(['status' => 'interview_user']),
            'scheduled_at' => now()->addDay(),
            'location' => 'Ruang Meeting A',
            'interviewer_id' => User::factory()->state(['is_active' => true]),
            'status' => 'scheduled',
        ];
    }
}
