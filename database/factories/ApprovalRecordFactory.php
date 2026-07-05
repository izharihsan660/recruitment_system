<?php

namespace Database\Factories;

use App\Models\ApprovalChain;
use App\Models\ApprovalRecord;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRecord>
 */
class ApprovalRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recruitment_request_id' => RecruitmentRequest::factory(),
            'approval_chain_id' => ApprovalChain::factory(),
            'approver_id' => null,
            'action' => 'waiting',
            'comment' => null,
            'acted_at' => null,
        ];
    }
}
