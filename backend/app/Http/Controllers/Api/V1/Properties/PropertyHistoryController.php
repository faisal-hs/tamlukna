<?php

namespace App\Http\Controllers\Api\V1\Properties;

use App\Http\Controllers\Api\V1\Properties\Concerns\ResolvesPropertyAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyStatusHistoryResource;
use App\Models\Property;
use App\Models\PropertyStatusHistory;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PropertyHistoryController extends Controller
{
    use ResolvesPropertyAccess;

    public function __invoke(Request $request, Property $property): JsonResponse
    {
        $this->authorizeProperty($this->user($request), 'viewHistory', $property);

        $history = $property->statusHistory()->get()
            ->each(fn (PropertyStatusHistory $entry) => $entry->setRelation('property', $property));

        return ApiResponse::success(PropertyStatusHistoryResource::collection($history));
    }
}
