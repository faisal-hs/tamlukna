<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords) {}

    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwords->sendResetLink((string) $request->validated('email'));

        return ApiResponse::success(message: (string) __('api.auth.reset_link_sent'));
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwords->reset(
            (string) $request->validated('email'),
            (string) $request->validated('token'),
            (string) $request->validated('password'),
        );

        return ApiResponse::success(message: (string) __('api.auth.password_reset'));
    }
}
