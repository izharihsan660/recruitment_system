<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'tagline' => fake()->sentence(),
            'hero_image_path' => null,
            'about' => fake()->paragraph(),
            'values' => [],
            'gallery' => [],
            'address' => fake()->address(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
