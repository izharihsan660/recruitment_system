<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GraphApiConfig>
 */
class GraphApiConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fake()->uuid(),
            'client_id' => fake()->uuid(),
            'client_secret' => 'graph-secret',
            'calendar_user_email' => fake()->safeEmail(),
            'is_active' => false,
        ];
    }
}
