<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('user.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('user.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.edit');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.delete');
    }

    public function invite(User $user): bool
    {
        return $user->hasAnyRole([
            'super-admin','company-admin','subsidiary-admin', 'branch-admin'
        ]);
    }

}
