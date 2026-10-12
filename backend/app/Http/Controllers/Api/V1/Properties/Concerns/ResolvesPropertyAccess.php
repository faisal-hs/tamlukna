<?php

namespace App\Http\Controllers\Api\V1\Properties\Concerns;

use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

trait ResolvesPropertyAccess
{
    /**
     * Authenticated, active user (routes behind auth:sanctum + active).
     */
    protected function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * Listings the user cannot see → 404 (existence not revealed); visible but
     * the ability is denied → 403.
     */
    protected function authorizeProperty(User $user, string $ability, Property $property): void
    {
        if (Gate::forUser($user)->denies('view', $property)) {
            abort(404);
        }

        Gate::forUser($user)->authorize($ability, $property);
    }

    protected function present(Property $property, bool $includePrivate): PropertyResource
    {
        $property->loadMissing(PropertyResource::RELATIONS);

        return (new PropertyResource($property))->withPrivateFields($includePrivate);
    }
}
