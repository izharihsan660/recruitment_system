<?php

namespace Database\Factories;

use App\Models\GraphMailSenderSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GraphMailSenderSetting>
 */
class GraphMailSenderSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => fake()->uuid(),
            'client_id' => fake()->uuid(),
            'client_secret' => fake()->password(32),
            'sender_mailbox' => fake()->unique()->companyEmail(),
            'from_name' => fake()->company(),
            'is_active' => true,
        ];
    }
}
