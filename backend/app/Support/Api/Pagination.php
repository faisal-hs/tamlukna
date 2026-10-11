<?php

namespace App\Support\Api;

use Illuminate\Http\Request;

/**
 * Shared pagination rules. Form Requests merge rules(); controllers use perPage().
 */
final class Pagination
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::max()],
        ];
    }

    public static function perPage(Request $request): int
    {
        $requested = $request->integer('per_page', (int) config('tamlukna.pagination.default_per_page', 15));

        return max(1, min(self::max(), $requested));
    }

    private static function max(): int
    {
        return (int) config('tamlukna.pagination.max_per_page', 50);
    }
}
