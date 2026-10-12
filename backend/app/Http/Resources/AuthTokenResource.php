<?php

namespace App\Http\Resources;

use App\Services\Auth\IssuedToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssuedToken $issued */
        $issued = $this->resource;

        return [
            'user' => (new UserResource($issued->user))->resolve($request),
            'token' => [
                'access_token' => $issued->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $issued->expiresAt?->toIso8601ZuluString(),
            ],
        ];
    }
}
