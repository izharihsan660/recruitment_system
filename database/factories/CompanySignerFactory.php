<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanySigner>
 */
class CompanySignerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entity_id' => Entity::factory(),
            'user_id' => User::factory(),
            'document_type' => fake()->randomElement(['offering', 'pkwt']),
            'is_active' => true,
        ];
    }
}
