<?php

namespace App\Providers;

use App\Services\SchoolContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SchoolContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->toString()));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $activeMemberships = $user?->memberships()->active()->count() ?? 0;

            $view->with([
                'currentSchool' => app(SchoolContext::class)->school(),
                'canSwitchSchool' => $user !== null && ($user->is_platform_admin || $activeMemberships > 1),
            ]);
        });
    }
}
