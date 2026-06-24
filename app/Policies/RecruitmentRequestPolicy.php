<?php

namespace App\Policies;

use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Roles;

class RecruitmentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Roles::HrRecruiter, Roles::HrManager, Roles::HiringManager]);
    }

    public function view(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return $this->isHr($user) || ((int) $user->department_id === (int) $recruitmentRequest->department_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Roles::HrRecruiter, Roles::HrManager, Roles::HiringManager]);
    }

    public function update(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return in_array($recruitmentRequest->status, ['draft', 'need_revision'], true)
            && ($this->isHr($user) || (int) $user->id === (int) $recruitmentRequest->requester_id);
    }

    public function submit(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return $this->update($user, $recruitmentRequest);
    }

    public function approve(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return $user->hasAnyRole([Roles::Approver, Roles::HrRecruiter, Roles::HrManager]);
    }

    public function reject(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return $this->approve($user, $recruitmentRequest);
    }

    public function needRevision(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return $this->approve($user, $recruitmentRequest);
    }

    public function close(User $user, RecruitmentRequest $recruitmentRequest): bool
    {
        return $this->isHr($user) && $recruitmentRequest->status === 'approved';
    }

    private function isHr(User $user): bool
    {
        return $user->hasAnyRole([Roles::HrRecruiter, Roles::HrManager]);
    }
}
