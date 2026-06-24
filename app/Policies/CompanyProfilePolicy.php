<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;

class CompanyProfilePolicy
{
    public function update(User $user): bool
    {
        return $user->hasRole(Roles::Admin);
    }
}
