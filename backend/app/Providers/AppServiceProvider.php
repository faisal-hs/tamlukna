<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureEloquent();
        $this->configureRateLimiting();
        $this->configurePasswordRules();
    }

    /**
     * Outside production: lazy loading (N+1), silently discarded attributes and
     * missing attributes throw. In production, lazy loading is logged instead.
     */
    private function configureEloquent(): void
    {
        $strict = ! $this->app->isProduction();

        Model::preventLazyLoading();
        Model::preventSilentlyDiscardingAttributes($strict);
        Model::preventAccessingMissingAttributes($strict);

        if (! $strict) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                Log::warning('Lazy loading violation', ['model' => $model::class, 'relation' => $relation]);
            });
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()
                ? 'user:'.$request->user()->getAuthIdentifier()
                : 'ip:'.$request->ip();

            return Limit::perMinute((int) config('tamlukna.rate_limits.api_per_minute', 60))->by($key);
        });

        // Registration and login, per IP. Login also has a per-email lockout
        // in AuthenticationService.
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute((int) config('tamlukna.rate_limits.auth_per_minute', 10))->by('ip:'.$request->ip());
        });
    }

    /**
     * Minimum 8 characters with letters and numbers. In production, passwords
     * found in known data breaches are also rejected (Have I Been Pwned range API).
     */
    private function configurePasswordRules(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(8)->letters()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
