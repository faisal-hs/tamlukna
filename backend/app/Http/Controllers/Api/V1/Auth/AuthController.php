<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthenticationService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $issued = $this->auth->register(
            $request->safe()->except('device_name'),
            $this->deviceName($request),
        );

        return ApiResponse::created(new AuthTokenResource($issued), (string) __('api.auth.registered'));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $issued = $this->auth->login(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
            (string) $request->ip(),
            $this->deviceName($request),
        );

        return ApiResponse::success(new AuthTokenResource($issued), (string) __('api.auth.logged_in'));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->revokeCurrentToken($this->user($request));

        return ApiResponse::success(message: (string) __('api.auth.logged_out'));
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->auth->revokeAllTokens($this->user($request));

        return ApiResponse::success(message: (string) __('api.auth.logged_out_all'));
    }

    private function deviceName(Request $request): ?string
    {
        $name = $request->input('device_name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
