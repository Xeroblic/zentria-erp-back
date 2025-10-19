<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('view-user');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-user');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('edit-user');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('delete-user');
    }

    public function invite(User $user): bool
    {
        return $user->hasAnyRole([
            'super-admin','company-admin','subsidiary-admin', 'branch-admin'
        ]);
    }

}
