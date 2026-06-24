<?php

namespace Database\Factories;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InAppNotification>
 */
class InAppNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'fpk.submitted',
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
            'data' => [],
            'read_at' => null,
        ];
    }
}
