<?php

namespace App\Exceptions;

use App\Support\Api\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every exception raised under /api/* in the standard envelope.
 * Laravel converts ModelNotFound/Authorization exceptions to HTTP exceptions
 * before this runs, so they arrive here as 404/403.
 */
final class ApiExceptionRenderer
{
    /** @var array<int, string> HTTP status => stable machine-readable code */
    private const CODES = [
        400 => 'bad_request',
        401 => 'unauthenticated',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        409 => 'conflict',
        413 => 'payload_too_large',
        415 => 'unsupported_media_type',
        419 => 'session_expired',
        429 => 'too_many_requests',
        503 => 'service_unavailable',
    ];

    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') || $e instanceof HttpResponseException) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => ApiResponse::error(
                message: (string) __('api.errors.validation_failed'),
                status: $e->status,
                code: 'validation_failed',
                errors: $e->errors(),
            ),
            $e instanceof AuthenticationException => ApiResponse::error(
                message: (string) __('api.errors.unauthenticated'),
                status: 401,
                code: 'unauthenticated',
            ),
            $e instanceof HttpExceptionInterface => $this->renderHttpException($e),
            default => ApiResponse::error(
                message: (string) __('api.errors.server_error'),
                status: 500,
                code: 'server_error',
                meta: $this->debugMeta($e),
            ),
        };
    }

    private function renderHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();
        $code = self::CODES[$status] ?? ($status >= 500 ? 'server_error' : 'http_error');

        return ApiResponse::error(
            message: (string) __("api.errors.{$code}"),
            status: $status,
            code: $code,
            meta: $status >= 500 ? $this->debugMeta($e) : [],
            headers: $e->getHeaders(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function debugMeta(Throwable $e): array
    {
        if (! config('app.debug')) {
            return [];
        }

        return ['debug' => [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]];
    }
}
