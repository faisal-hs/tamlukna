<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PasswordResetService
{
    /**
     * The broker's result (sent / unknown user / throttled) is deliberately
     * ignored so the response never reveals whether an account exists.
     */
    public function sendResetLink(string $email): void
    {
        Password::broker()->sendResetLink(['email' => $email]);
    }

    /**
     * Sets the new password and revokes every API token (all devices) atomically.
     * Unknown email and invalid/expired token produce the same error.
     */
    public function reset(string $email, string $token, string $password): void
    {
        $status = Password::broker()->reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $user, string $newPassword): void {
                DB::transaction(function () use ($user, $newPassword): void {
                    $user->forceFill([
                        'password' => $newPassword,
                        'remember_token' => Str::random(60),
                    ])->save();

                    $user->tokens()->delete();
                });

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => (string) __('passwords.token')]);
        }
    }
}
