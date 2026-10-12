<?php

namespace App\Http\Requests\Properties;

use App\Enums\ListingStatus;
use App\Support\Api\Pagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MyPropertiesRequest extends FormRequest
{
    /**
     * Server-side sort allowlist: client value => [column, direction].
     */
    private const SORTS = [
        'created_at' => ['created_at', 'asc'],
        '-created_at' => ['created_at', 'desc'],
        'updated_at' => ['updated_at', 'asc'],
        '-updated_at' => ['updated_at', 'desc'],
        'price' => ['price', 'asc'],
        '-price' => ['price', 'desc'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(Pagination::rules(), [
            'status' => ['sometimes', 'string', Rule::enum(ListingStatus::class)],
            'sort' => ['sometimes', 'string', Rule::in(array_keys(self::SORTS))],
        ]);
    }

    /**
     * @return array{string, string}
     */
    public function sort(): array
    {
        $sort = $this->validated('sort', '-created_at');

        return self::SORTS[is_string($sort) ? $sort : '-created_at'];
    }
}
