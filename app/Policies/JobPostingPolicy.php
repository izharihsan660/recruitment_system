<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;
use App\Support\Roles;

class JobPostingPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(Roles::Admin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, JobPosting $jobPosting): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $this->isHr($user);
    }

    private function isHr(User $user): bool
    {
        return $user->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]);
    }
}
