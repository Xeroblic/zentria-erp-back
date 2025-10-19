<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Reglas:
    // - super-admin pasa por Gate::before (ver más abajo).
    // - "admin" o permiso global pueden operar sobre cualquiera.
    // - self: el propio usuario puede verse/editarse con permisos acotados.

    public function viewAny(User $actor): bool
    {
        return $user->hasPermissionTo('view-user');
    }

    public function view(User $actor, User $target): bool
    {
        return $user->hasPermissionTo('view-user');
    }

    public function create(User $actor): bool
    {
        return $user->hasPermissionTo('create-user');
    }

    public function update(User $actor, User $target): bool
    {
        return $user->hasPermissionTo('edit-user');
    }

    public function delete(User $actor, User $target): bool
    {
        return $user->hasPermissionTo('delete-user');
    }

    public function invite(User $actor): bool
    {
        // Si prefieres permiso en vez de roles, usa: hasPermissionTo('user.invite','api')
        return $actor->hasAnyRole(['super-admin','company-admin','subsidiary-admin','branch-admin']);
    }
}