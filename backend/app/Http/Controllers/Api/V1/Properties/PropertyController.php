<?php

namespace App\Http\Controllers\Api\V1\Properties;

use App\Http\Controllers\Api\V1\Properties\Concerns\ResolvesPropertyAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Properties\StorePropertyRequest;
use App\Http\Requests\Properties\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\User;
use App\Services\Properties\PropertyService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PropertyController extends Controller
{
    use ResolvesPropertyAccess;

    public function __construct(private readonly PropertyService $properties) {}

    /**
     * Public endpoint with optional authentication: a valid bearer token of an
     * active account can additionally see that account's unpublished listings.
     */
    public function show(Request $request, Property $property): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $viewer = $viewer instanceof User && $viewer->status->canAuthenticate() ? $viewer : null;

        if (Gate::forUser($viewer)->denies('view', $property)) {
            abort(404);
        }

        $includePrivate = $viewer !== null && Gate::forUser($viewer)->allows('update', $property);

        return ApiResponse::success($this->present($property, $includePrivate));
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->properties->create(
            $this->user($request),
            $request->contentData(),
            $request->featureIds(),
            $request,
        );

        return ApiResponse::created($this->present($property, true), (string) __('api.properties.created'));
    }

    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $updated = $this->properties->update(
            $property,
            $request->contentData(),
            $request->featureIds(),
            $this->user($request),
            $request,
        );

        return ApiResponse::success($this->present($updated, true), (string) __('api.properties.updated'));
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        $user = $this->user($request);
        $this->authorizeProperty($user, 'delete', $property);

        $this->properties->delete($property, $user, $request);

        return ApiResponse::success(message: (string) __('api.properties.deleted'));
    }
}
