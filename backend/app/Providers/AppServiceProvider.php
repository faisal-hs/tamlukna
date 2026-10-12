<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        $this->configureUrls();
        $this->configureEloquent();
        $this->configureRateLimiting();
        $this->configurePasswordRules();
        $this->configureAuthLinks();
    }

    /**
     * Generated absolute URLs (e.g. signed verification links) always use APP_URL,
     * never the client-supplied Host header. This prevents Host-header injection
     * into emailed links.
     */
    private function configureUrls(): void
    {
        $root = (string) config('app.url');

        if ($root === '') {
            return;
        }

        URL::forceRootUrl($root);

        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
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

        // Unauthenticated auth endpoints, per IP. Login also has a per-email
        // lockout in AuthenticationService.
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

    /**
     * Verification links use the public ULID (never the internal ID) and point
     * at the API. Password reset links point at the configurable frontend page.
     */
    private function configureAuthLinks(): void
    {
        VerifyEmail::createUrlUsing(function (User $notifiable): string {
            return URL::temporarySignedRoute(
                'api.v1.auth.verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->public_id,
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });

        ResetPassword::createUrlUsing(function (User $notifiable, string $token): string {
            return strtr((string) config('tamlukna.frontend.password_reset_url'), [
                '{token}' => urlencode($token),
                '{email}' => urlencode($notifiable->getEmailForPasswordReset()),
            ]);
        });
    }
}
