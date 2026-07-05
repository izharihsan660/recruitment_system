<?php

namespace App\Services;

use App\Models\ApprovalChain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApprovalChainService extends AdminCrudService
{
    public function createMany(int $departmentId, array $userIds): void
    {
        DB::transaction(function () use ($departmentId, $userIds): void {
            foreach ($userIds as $userId) {
                ApprovalChain::query()->firstOrCreate(
                    [
                        'department_id' => $departmentId,
                        'approver_user_id' => $userId,
                    ],
                    [
                        'type' => 'user',
                        'approver_role' => null,
                    ]
                );
            }
        });
    }

    public function create(array $data): Model
    {
        return parent::create($this->normalizeApproverData($data));
    }

    public function update(Model $model, array $data): Model
    {
        return parent::update($model, $this->normalizeApproverData($data));
    }

    protected function modelClass(): string
    {
        return ApprovalChain::class;
    }

    private function normalizeApproverData(array $data): array
    {
        $data['type'] = 'user';
        $data['approver_role'] = null;

        return $data;
    }
}
