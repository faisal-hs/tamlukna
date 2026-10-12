<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Api\InvalidVerificationLinkException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $verification) {}

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        // Covers tampered, truncated and expired links.
        if (! $request->hasValidSignature()) {
            throw new InvalidVerificationLinkException;
        }

        $verifiedNow = $this->verification->verify($id, $hash);

        return ApiResponse::success(message: (string) __(
            $verifiedNow ? 'api.auth.email_verified' : 'api.auth.email_already_verified'
        ));
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $sent = $this->verification->resend($user);

        return ApiResponse::success(message: (string) __(
            $sent ? 'api.auth.verification_sent' : 'api.auth.email_already_verified'
        ));
    }
}
