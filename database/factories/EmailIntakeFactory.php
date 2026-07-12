<?php

namespace Database\Factories;

use App\Models\EmailIntake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailIntake>
 */
class EmailIntakeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'graph_message_id' => fake()->uuid(),
            'sender_name' => fake()->name(),
            'sender_email' => fake()->unique()->safeEmail(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'phone_number' => null,
            'received_at' => now(),
            'attachment_path' => null,
            'suggested_job_id' => null,
            'status' => 'need_review',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'candidate_id' => null,
            'is_duplicate' => false,
        ];
    }
}
