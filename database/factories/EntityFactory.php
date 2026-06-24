<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entity>
 */
class EntityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'short_name' => strtoupper(fake()->unique()->lexify('???')),
            'is_active' => true,
        ];
    }
}
