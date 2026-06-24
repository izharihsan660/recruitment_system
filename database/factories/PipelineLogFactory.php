<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\PipelineLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PipelineLog>
 */
class PipelineLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'from_stage' => 'applied',
            'to_stage' => 'screening',
            'actor_id' => User::factory(),
            'notes' => null,
            'created_at' => now(),
        ];
    }
}
