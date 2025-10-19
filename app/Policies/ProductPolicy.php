<?php
namespace App\Policies;

use App\Models\Product;
use App\Models\Branch;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user, Branch $branch): bool {
        if ($user->hasRole('super-admin')) return true;
        return $user->canAccessEntity('branch', $branch->id);
    }
    public function view(User $user, Product $product): bool {
        if ($user->hasRole('super-admin')) return true;
        return $user->canAccessEntity('branch', $product->branch_id);
    }
    public function create(User $user, Branch $branch): bool {
        if ($user->hasRole('super-admin')) return true;
        return $user->hasPermissionTo('create-product') && $user->canAccessEntity('branch', $branch->id);
    }
    public function update(User $user, Product $product): bool {
        if ($user->hasRole('super-admin')) return true;
        return $user->hasPermissionTo('edit-product') && $user->canAccessEntity('branch', $product->branch_id);
    }
    public function delete(User $user, Product $product): bool {
        if ($user->hasRole('super-admin')) return true;
        return $user->hasPermissionTo('delete-product') && $user->canAccessEntity('branch', $product->branch_id);
    }
}
