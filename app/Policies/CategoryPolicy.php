<?php
namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {   return $user->hasRole('super-admin') || $user->hasPermissionTo('view-category'); }

    public function view(User $user, Category $category): bool
    {   return $user->hasRole('super-admin') || $user->hasPermissionTo('view-category'); }

    public function create(User $user): bool
    {   return $user->hasRole('super-admin') || $user->hasPermissionTo('create-category'); }

    public function update(User $user, Category $category): bool
    {   return $user->hasRole('super-admin') || $user->hasPermissionTo('edit-category'); }

    public function delete(User $user, Category $category): bool
    {   return $user->hasRole('super-admin') || $user->hasPermissionTo('delete-category'); }
}
