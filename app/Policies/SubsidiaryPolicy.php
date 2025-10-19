<?php

namespace App\Policies;

use App\Models\Subsidiary;
use App\Models\User;

class SubsidiaryPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin')) return true;

        // Cualquier acceso contextual: branches directas o roles en subsidiary/company
        if ($user->branches()->exists()) return true;

        return $user->scopeRoles()
            ->whereIn('scope_type', ['subsidiary','company'])
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['subsidiary-member','company-member','subsidiary-admin','company-admin']);
            })
            ->exists();
    }
    public function view(User $user, Subsidiary $subsidiary): bool
    {
        if ($user->hasRole('super-admin')) return true;

        // Acceso por pertenencia directa a alguna branch de la subsidiary
        $hasDirectBranch = $subsidiary->branches()
            ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->exists();

        // Acceso por roles contextuales (miembro o admin)
        $hasContext = (
            $user->hasContextRole('subsidiary-admin', 'subsidiary', $subsidiary->id) ||
            $user->hasContextRole('company-admin', 'company', $subsidiary->company_id) ||
            $user->hasContextRole('subsidiary-member', 'subsidiary', $subsidiary->id) ||
            $user->hasContextRole('company-member', 'company', $subsidiary->company_id)
        );

        return $hasDirectBranch || $hasContext;
    }

    public function create(User $user): bool
    {
        return $user->can('create-subsidiary');
    }

    public function update(User $user, Subsidiary $subsidiary): bool
    {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->can('edit-subsidiary')) return false;

        return (
            $user->hasContextRole('subsidiary-admin', 'subsidiary', $subsidiary->id) ||
            $user->hasContextRole('company-admin', 'company', $subsidiary->company_id)
        );
    }

    public function delete(User $user, Subsidiary $subsidiary): bool
    {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->can('delete-subsidiary')) return false;

        return $user->hasContextRole('company-admin', 'company', $subsidiary->company_id);
    }
}
