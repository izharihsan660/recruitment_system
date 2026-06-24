<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\TalentPool;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalentPool>
 */
class TalentPoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'status' => 'active',
            'tags' => ['operator', 'experienced'],
            'notes' => fake()->sentence(),
            'source_application_id' => Application::factory(),
            'added_by' => User::factory(),
            'added_at' => now(),
        ];
    }
}
