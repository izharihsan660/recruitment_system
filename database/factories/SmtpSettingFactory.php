<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmtpSetting>
 */
class SmtpSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'host' => 'smtp.example.test',
            'port' => 587,
            'username' => fake()->userName(),
            'password' => 'secret-password',
            'encryption' => 'tls',
            'from_address' => fake()->safeEmail(),
            'from_name' => fake()->company(),
            'is_active' => false,
        ];
    }
}
