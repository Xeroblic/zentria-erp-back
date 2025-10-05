<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\UserPersonalization;
use App\Policies\BranchPolicy;
use App\Policies\UserPersonalizationPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        UserPersonalization::class => UserPersonalizationPolicy::class,
        User::class => UserPolicy::class,
        Branch::class => BranchPolicy::class,
        Product::class => ProductPolicy::class,
        Brand::class => BrandPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    public function register(): void
    {
    }
    

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('show-profile', function ($user){ 
            $personalization = $user->personalization;
                if (!$personalization) {
                return true;
            }

            return $user->can('view', $personalization);
        });
        
        Gate::define('edit-profile', function ($user) {
            $personalization = $user->personalization;

            if (!$personalization) {
                return true;
            }
            return $user->can('update', $personalization);
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
        
    }
}
