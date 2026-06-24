<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'birth_date' => now()->subYears(25)->toDateString(),
            'gender' => 'male',
            'cv_path' => null,
            'cv_original_name' => null,
            'education' => [],
            'experience' => [],
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function withCv(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cv_path' => 'cv/test/cv.pdf',
            'cv_original_name' => 'cv.pdf',
        ]);
    }
}
