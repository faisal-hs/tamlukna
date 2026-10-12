<?php

namespace App\Http\Resources;

use App\Models\PropertyStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Who changed the status is shown as a role label, never as a staff identity.
 * Requires the "property" relation to be set.
 *
 * @mixin PropertyStatusHistory
 */
final class PropertyStatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'from' => $this->from_status?->value,
            'to' => $this->to_status->value,
            'reason' => $this->reason,
            'actor' => match (true) {
                $this->actor_id === null => 'system',
                $this->actor_id === $this->property->advertiser_id => 'advertiser',
                default => 'moderator',
            },
            'created_at' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
