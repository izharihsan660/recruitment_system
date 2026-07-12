<?php

namespace Database\Factories;

use App\Models\EmailIntakeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailIntakeSetting>
 */
class EmailIntakeSettingFactory extends Factory
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
            'client_secret' => 'intake-secret',
            'mailbox_address' => 'karir@example.test',
            'is_active' => true,
            'last_synced_at' => null,
            'last_received_at' => null,
            'sync_interval_minutes' => 10,
        ];
    }
}
