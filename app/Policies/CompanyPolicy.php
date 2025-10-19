<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin')) return true;

        if ($user->companies()->exists()) return true;

        return $user->scopeRoles()
            ->where('scope_type', 'company')
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['company-member','company-admin']);
            })
            ->exists();
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->hasRole('super-admin')) return true;

        $hasPermission = $user->can('view-company');
        $hasContext = (
            $user->companies->contains('id', $company->id) ||
            (method_exists($user, 'belongsToCompany') && $user->belongsToCompany($company->id)) ||
            $user->hasContextRole('company-admin', 'company', $company->id) ||
            $user->hasContextRole('company-member', 'company', $company->id)
        );

        return $hasPermission || $hasContext;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) return true;
        return $user->can('create-company');
    }

    public function update(User $user, Company $company): bool
    {
        if ($user->hasRole('super-admin')) return true;
        return $user->can('edit-company') || $user->hasContextRole('company-admin', 'company', $company->id);
    }

    public function delete(User $user, Company $company): bool
    {
        if ($user->hasRole('super-admin')) return true;
        return $user->can('delete-company') || $user->hasContextRole('company-admin', 'company', $company->id);
    }
}

