<?php

namespace App\Policies;

use App\Models\Screening;
use App\Models\User;
use App\Support\Roles;

class ScreeningPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Screening $screening): bool
    {
        return $user->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Screening $screening): bool
    {
        return $user->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Screening $screening): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Screening $screening): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Screening $screening): bool
    {
        return false;
    }
}
