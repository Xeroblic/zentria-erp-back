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
        return $actor->hasPermissionTo('user.view', 'api');
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->hasPermissionTo('user.view', 'api')) {
            return true;
        }
        // Permitimos ver su propio perfil con permiso acotado
        return $actor->id === $target->id && $actor->hasPermissionTo('user.view.self', 'api');
    }

    public function create(User $actor): bool
    {
        // Crea usuarios: admin o permiso específico
        return $actor->hasRole('admin') || $actor->hasPermissionTo('user.create', 'api');
    }

    public function update(User $actor, User $target): bool
    {
        // Admin o permiso global
        if ($actor->hasRole('admin') || $actor->hasPermissionTo('user.edit', 'api')) {
            return true;
        }
        // Self-edit con permiso acotado
        return $actor->id === $target->id && $actor->hasPermissionTo('user.edit.self', 'api');
    }

    public function delete(User $actor, User $target): bool
    {
        // Evita auto-borrado (opcional, recomendado)
        if ($actor->id === $target->id) {
            return false;
        }
        return $actor->hasRole('admin') || $actor->hasPermissionTo('user.delete', 'api');
    }

    public function invite(User $actor): bool
    {
        // Si prefieres permiso en vez de roles, usa: hasPermissionTo('user.invite','api')
        return $actor->hasAnyRole(['super-admin','company-admin','subsidiary-admin','branch-admin']);
    }
}