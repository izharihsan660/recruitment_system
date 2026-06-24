<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create(Arr::except($data, ['roles']));
            $user->assignRole($data['roles'] ?? []);

            return $user->refresh();
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update(Arr::except($data, ['roles', 'password']));

            if (array_key_exists('password', $data) && filled($data['password'])) {
                $user->forceFill(['password' => $data['password']])->save();
            }

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles']);
            }

            return $user->refresh();
        });
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
