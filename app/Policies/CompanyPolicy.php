<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Company;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->hasPermissionTo('company.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('company.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasPermissionTo('company.edit');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasPermissionTo('company.delete');
    }
}
