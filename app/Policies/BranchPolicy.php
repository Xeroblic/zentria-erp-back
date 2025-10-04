<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->can('view-branch')) return false;

        return (
            $user->branches->contains('id', $branch->id) ||
            $user->hasContextRole('branch-admin', 'branch', $branch->id) ||
            $user->hasContextRole('subsidiary-admin', 'subsidiary', $branch->subsidiary_id) ||
            $user->hasContextRole('company-admin', 'company', $branch->subsidiary->company_id)
        );
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
