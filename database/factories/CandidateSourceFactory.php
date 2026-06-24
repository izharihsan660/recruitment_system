<?php

namespace Database\Factories;

use App\Models\CandidateSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateSource>
 */
class CandidateSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Walk-in', 'LinkedIn', 'JobStreet', 'Instagram', 'Email Manual', 'Referral']),
            'is_active' => true,
        ];
    }
}
