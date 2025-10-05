<?php
namespace App\Policies;

use App\Models\Product;
use App\Models\Branch;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user, Branch $branch): bool {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('view-product')) return false;
        return $user->canAccessEntity('branch', $branch->id);
    }
    public function view(User $user, Product $product): bool {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('view-product')) return false;
        return $user->canAccessEntity('branch', $product->branch_id);
    }
    public function create(User $user, Branch $branch): bool {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('create-product')) return false;
        return $user->canAccessEntity('branch', $branch->id);
    }
    public function update(User $user, Product $product): bool {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('edit-product')) return false;
        return $user->canAccessEntity('branch', $product->branch_id);
    }
    public function delete(User $user, Product $product): bool {
        if ($user->hasRole('super-admin')) return true;
        if (!$user->hasPermissionTo('delete-product')) return false;
        return $user->canAccessEntity('branch', $product->branch_id);
    }
}
