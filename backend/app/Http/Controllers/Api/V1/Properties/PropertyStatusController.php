<?php

namespace App\Http\Controllers\Api\V1\Properties;

use App\Http\Controllers\Api\V1\Properties\Concerns\ResolvesPropertyAccess;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Properties\ListingWorkflow;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Advertiser-side lifecycle actions. Moderation (approve/reject) is not exposed
 * here; see the Step 11 command and the Phase 2 dashboard API.
 */
final class PropertyStatusController extends Controller
{
    use ResolvesPropertyAccess;

    public function __construct(private readonly ListingWorkflow $workflow) {}

    public function submit(Request $request, Property $property): JsonResponse
    {
        $user = $this->user($request);
        $this->authorizeProperty($user, 'changeStatus', $property);

        $result = $this->workflow->submit($property, $user, $request);

        return ApiResponse::success($this->present($result, true), (string) __('api.properties.submitted'));
    }

    public function pause(Request $request, Property $property): JsonResponse
    {
        $user = $this->user($request);
        $this->authorizeProperty($user, 'changeStatus', $property);

        $result = $this->workflow->pause($property, $user, $request);

        return ApiResponse::success($this->present($result, true), (string) __('api.properties.paused'));
    }

    public function resume(Request $request, Property $property): JsonResponse
    {
        $user = $this->user($request);
        $this->authorizeProperty($user, 'changeStatus', $property);

        $result = $this->workflow->resume($property, $user, $request);

        return ApiResponse::success($this->present($result, true), (string) __('api.properties.resumed'));
    }
}
