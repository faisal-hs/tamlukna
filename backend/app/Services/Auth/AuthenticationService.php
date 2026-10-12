<?php

namespace App\Services\Auth;

use App\Exceptions\Api\AccountInactiveException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthenticationService
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    /**
     * Creates the account and its first token atomically. Without an explicit
     * preferred_locale, the language of the registration request is used.
     *
     * @param  array<string, mixed>  $data  Validated registration data
     */
    public function register(array $data, ?string $deviceName): IssuedToken
    {
        $issued = DB::transaction(function () use ($data, $deviceName): IssuedToken {
            $user = User::create([
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'password' => (string) $data['password'],
                'phone' => isset($data['phone']) ? (string) $data['phone'] : null,
                'preferred_locale' => (string) ($data['preferred_locale'] ?? App::getLocale()),
            ]);

            return $this->issueToken($user->refresh(), $deviceName);
        });

        event(new Registered($issued->user));

        return $issued;
    }

    /**
     * Wrong password and unknown email produce the same error with similar
     * timing, so the endpoint cannot be used to discover registered emails.
     * Account status is checked only after the password is verified.
     */
    public function login(string $email, string $password, string $ip, ?string $deviceName): IssuedToken
    {
        $key = 'login:'.Str::transliterate(Str::lower($email)).'|'.$ip;

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            throw new ThrottleRequestsException(headers: ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            Hash::make($password); // equalize timing with the password check
        }

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages(['email' => (string) __('auth.failed')]);
        }

        RateLimiter::clear($key);

        if (! $user->status->canAuthenticate()) {
            throw new AccountInactiveException;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->issueToken($user, $deviceName);
    }

    public function revokeCurrentToken(User $user): void
    {
        // A PersonalAccessToken for bearer tokens; a TransientToken once SPA cookie
        // mode is enabled (that case is handled by ending the session instead).
        /** @var HasAbilities|null $token */
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function revokeAllTokens(User $user): int
    {
        return (int) $user->tokens()->delete();
    }

    private function issueToken(User $user, ?string $deviceName): IssuedToken
    {
        $minutes = (int) config('sanctum.expiration');
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $token = $user->createToken($deviceName ?? 'api', ['*'], $expiresAt);

        return new IssuedToken($user, $token->plainTextToken, $expiresAt);
    }
}
