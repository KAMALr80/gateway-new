<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Rate limits for the public storefront API. The admin panel login is throttled by Fortify;
     * these protect the JWT endpoints the storefront uses.
     */
    protected function configureRateLimiting(): void
    {
        // Brute-force protection: per account+IP, plus a looser per-IP cap against credential stuffing.
        RateLimiter::for('api-login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);

        // Registration, password reset e-mails and reset attempts.
        RateLimiter::for('api-auth-sensitive', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(30)->by($request->ip()),
        ]);

        RateLimiter::for('api-token-refresh', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        // Cart / wishlist / address writes by a signed-in user.
        RateLimiter::for('api-user-writes', fn (Request $request) => Limit::perMinute(120)->by(
            (string) (auth('api')->id() ?? $request->ip())
        ));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
