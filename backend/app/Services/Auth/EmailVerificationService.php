<?php

namespace App\Services\Auth;

use App\Exceptions\Api\InvalidVerificationLinkException;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

final class EmailVerificationService
{
    /**
     * Call only after the link's signature has been validated.
     *
     * @return bool true if the email was verified now; false if it already was
     */
    public function verify(string $publicId, string $hash): bool
    {
        $user = User::query()->where('public_id', $publicId)->first();

        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new InvalidVerificationLinkException;
        }

        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return true;
    }

    /**
     * @return bool true if a new link was sent; false if already verified
     */
    public function resend(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }
}
