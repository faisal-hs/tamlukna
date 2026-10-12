<?php

namespace App\Http\Controllers\Api\V1\Properties;

use App\Http\Controllers\Api\V1\Properties\Concerns\ResolvesPropertyAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Properties\MyPropertiesRequest;
use App\Http\Resources\MyPropertyResource;
use App\Http\Resources\PropertyResource;
use App\Support\Api\ApiResponse;
use App\Support\Api\Pagination;
use Illuminate\Http\JsonResponse;

/**
 * The authenticated account's own listings (any status except deleted).
 */
final class MyPropertyController extends Controller
{
    use ResolvesPropertyAccess;

    public function __invoke(MyPropertiesRequest $request): JsonResponse
    {
        $query = $this->user($request)->properties()->with(PropertyResource::RELATIONS);

        $status = $request->validated('status');
        if (is_string($status)) {
            $query->where('status', $status);
        }

        [$column, $direction] = $request->sort();

        $page = $query->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->paginate(Pagination::perPage($request));

        return ApiResponse::paginated($page, MyPropertyResource::class);
    }
}
