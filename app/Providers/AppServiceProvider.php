<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Paramètres de la plateforme, chargés une fois par requête pour les vues.
        $this->app->scoped('gkas.platform', function () {
            try {
                return Schema::hasTable('settings') ? Setting::allValues() : Setting::defaults();
            } catch (Throwable) {
                return Setting::defaults();
            }
        });
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');
        Route::resourceVerbs(['create' => 'creer', 'edit' => 'modifier']);

        Password::defaults(fn () => app()->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());

        // ---- Autorisations globales (vérifiées côté serveur) ----
        Gate::define('access-admin', fn (User $user) => $user->isStaff() && $user->is_active);
        Gate::define('manage-platform', fn (User $user) => $user->isAdmin() && $user->is_active);
        Gate::define('manage-prospects', fn (User $user) => $user->isStaff() && $user->is_active);
        Gate::define('review-payments', fn (User $user) => $user->isAdmin() && $user->is_active);

        // ---- Limitation des tentatives ----
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('orders', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        View::composer('*', fn ($view) => $view->with('platform', app('gkas.platform')));
    }
}
