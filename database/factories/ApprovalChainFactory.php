<?php

namespace Database\Factories;

use App\Models\Department;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalChain>
 */
class ApprovalChainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'level' => 1,
            'type' => 'role',
            'approver_user_id' => null,
            'approver_role' => Roles::HrManager,
        ];
    }
}
