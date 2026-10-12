<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user's own profile. Never exposes the internal ID,
 * password hash, or remember token. Roles and permissions are informational
 * (for client UI); the server enforces every check independently.
 *
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['roles.permissions', 'permissions']);

        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'phone' => $this->phone,
            'phone_verified' => $this->phone_verified_at !== null,
            'preferred_locale' => $this->preferred_locale,
            'status' => $this->status->value,
            'roles' => $this->getRoleNames()->sort()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->unique()->sort()->values()->all(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
