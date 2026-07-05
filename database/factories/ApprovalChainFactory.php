<?php

namespace Database\Factories;

use App\Models\ApprovalChain;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalChain>
 */
class ApprovalChainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'type' => 'user',
            'approver_user_id' => User::factory(),
            'approver_role' => null,
        ];
    }
}
