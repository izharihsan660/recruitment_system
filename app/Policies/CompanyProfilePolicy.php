<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;

class CompanyProfilePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(Roles::Admin) ? true : null;
    }

    public function update(User $user): bool
    {
        return $user->hasRole(Roles::Admin);
    }
}
