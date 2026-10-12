<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The single place that builds API response bodies.
 *
 * Success: { success: true,  message, data, meta }
 * Error:   { success: false, message, code, data, errors?, meta }
 */
final class ApiResponse
{
    private const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'message' => $message ?? (string) __('api.ok'),
            'data' => self::resolve($data),
            'meta' => (object) $meta,
        ], $status, $headers, self::JSON_OPTIONS);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return self::success($data, $message, $meta, 201);
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  class-string<JsonResource>  $resource
     * @param  array<string, mixed>  $meta
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resource,
        ?string $message = null,
        array $meta = [],
    ): JsonResponse {
        $items = $resource::collection(collect($paginator->items()))->resolve(request());

        return self::success($items, $message, array_merge($meta, [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ]));
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function error(
        string $message,
        int $status,
        string $code,
        ?array $errors = null,
        mixed $data = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        $body = [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'data' => self::resolve($data),
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        $body['meta'] = (object) $meta;

        return new JsonResponse($body, $status, $headers, self::JSON_OPTIONS);
    }

    private static function resolve(mixed $data): mixed
    {
        return $data instanceof JsonResource ? $data->resolve(request()) : $data;
    }
}
