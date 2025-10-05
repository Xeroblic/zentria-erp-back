<?php
namespace App\Policies;

use App\Models\Brand;
use App\Models\Branch;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user, Branch $branch): bool
    {   if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('view-brand')) return false;
        return $user->canAccessEntity('branch', $branch->id);
    }
    public function view(User $user, Brand $brand): bool
    {   if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('view-brand')) return false;
        return $user->canAccessEntity('branch', $brand->branch_id);
    }
    public function create(User $user, Branch $branch): bool
    {   if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('create-brand')) return false;
        return $user->canAccessEntity('branch', $branch->id);
    }
    public function update(User $user, Brand $brand): bool
    {   if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('edit-brand')) return false;
        return $user->canAccessEntity('branch', $brand->branch_id);
    }
    public function delete(User $user, Brand $brand): bool
    {   if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('delete-brand')) return false;
        return $user->canAccessEntity('branch', $brand->branch_id);
    }
}
