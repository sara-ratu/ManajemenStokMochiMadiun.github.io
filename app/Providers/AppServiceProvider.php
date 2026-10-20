<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('cashier', fn (User $user): bool => $user->role === 'Kasir');
        Gate::define('view-sales', fn (User $user): bool => in_array($user->role, ['SuperAdmin', 'Kasir'], true));
        Gate::define('manage-products', fn (User $user): bool => $user->role === 'SuperAdmin');
        Gate::define('manage-stocks', fn (User $user): bool => $user->role === 'Kasir');
    }
}
