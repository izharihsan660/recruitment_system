<?php

namespace App\Services;

use App\Models\ApprovalChain;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalChainService extends AdminCrudService
{
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $approvalChain = parent::create($data);
            $this->validateDepartmentChain((int) $approvalChain->department_id);

            return $approvalChain;
        });
    }

    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $approvalChain = parent::update($model, $data);
            $this->validateDepartmentChain((int) $approvalChain->department_id);

            return $approvalChain;
        });
    }

    public function delete(Model $model): void
    {
        DB::transaction(function () use ($model) {
            $departmentId = (int) $model->department_id;
            parent::delete($model);
            $this->validateDepartmentChain($departmentId);
        });
    }

    protected function modelClass(): string
    {
        return ApprovalChain::class;
    }

    private function validateDepartmentChain(int $departmentId): void
    {
        $chains = ApprovalChain::query()
            ->where('department_id', $departmentId)
            ->orderBy('level')
            ->get();

        $this->ensureChainHasValidShape($chains);
    }

    /**
     * @param  Collection<int, ApprovalChain>  $chains
     */
    private function ensureChainHasValidShape(Collection $chains): void
    {
        if ($chains->isEmpty()) {
            throw ValidationException::withMessages([
                'department_id' => 'Setiap department minimal punya 1 approval level.',
            ]);
        }

        if ($chains->count() > 3) {
            throw ValidationException::withMessages([
                'level' => 'Maksimal 3 approval level per department.',
            ]);
        }

        $expectedLevels = range(1, $chains->count());
        if ($chains->pluck('level')->values()->all() !== $expectedLevels) {
            throw ValidationException::withMessages([
                'level' => 'Level approval harus berurutan tanpa gap.',
            ]);
        }

        $lastChain = $chains->last();
        if ($lastChain->type !== 'role' || $lastChain->approver_role !== Roles::HrManager || $lastChain->approver_user_id !== null) {
            throw ValidationException::withMessages([
                'approver_role' => 'Level terakhir harus bertipe role hr_manager.',
            ]);
        }

        $chains->slice(0, -1)->each(function (ApprovalChain $chain): void {
            if ($chain->type !== 'user' || $chain->approver_user_id === null || $chain->approver_role !== null) {
                throw ValidationException::withMessages([
                    'approver_user_id' => 'Level sebelum terakhir harus bertipe user.',
                ]);
            }
        });
    }
}
