<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin')) return true;

        if ($user->branches()->exists()) return true; // acceso directo a alguna branch

        return $user->scopeRoles()
            ->whereIn('scope_type', ['branch','subsidiary','company'])
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['branch-admin','subsidiary-member','company-member','subsidiary-admin','company-admin']);
            })
            ->exists();
    }
    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasRole('super-admin')) return true;
        $hasAccess = (
            $user->branches->contains('id', $branch->id) ||
            $user->hasContextRole('branch-admin', 'branch', $branch->id) ||
            $user->hasContextRole('subsidiary-admin', 'subsidiary', $branch->subsidiary_id) ||
            $user->hasContextRole('company-admin', 'company', $branch->subsidiary->company_id) ||
            $user->hasContextRole('subsidiary-member', 'subsidiary', $branch->subsidiary_id) ||
            $user->hasContextRole('company-member', 'company', $branch->subsidiary->company_id)
        );

        return $hasAccess;
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->can('edit-branch')) return false;

        return (
            $user->branches->contains('id', $branch->id) ||
            $user->hasContextRole('branch-admin', 'branch', $branch->id) ||
            $user->hasContextRole('subsidiary-admin', 'subsidiary', $branch->subsidiary_id)
        );
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->can('delete-branch')) return false;

        return $user->hasContextRole('company-admin', 'company', $branch->subsidiary->company_id);
    }
}
