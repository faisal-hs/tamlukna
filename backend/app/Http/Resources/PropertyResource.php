<?php

namespace App\Http\Resources;

use App\Models\Feature;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Listing details. Public by default; private fields (exact address, internal
 * dates) only for those who manage the listing. Money amounts are decimal strings.
 * Never exposes internal IDs, storage paths or advertiser contact details.
 *
 * @mixin Property
 */
class PropertyResource extends JsonResource
{
    /** Relations the resource needs; load them before rendering (no lazy loading). */
    public const RELATIONS = ['propertyType', 'city', 'district', 'features', 'advertiser'];

    protected bool $includePrivate = false;

    public function withPrivateFields(bool $include = true): static
    {
        $this->includePrivate = $include;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'purpose' => $this->purpose->value,
            'rent_period' => $this->rent_period?->value,
            'property_type' => [
                'slug' => $this->propertyType->slug,
                'name_ar' => $this->propertyType->name_ar,
                'name_en' => $this->propertyType->name_en,
                'category' => $this->propertyType->category->value,
            ],
            'title' => ['ar' => $this->title_ar, 'en' => $this->title_en],
            'description' => ['ar' => $this->description_ar, 'en' => $this->description_en],
            'location' => [
                'city' => ['slug' => $this->city->slug, 'name_ar' => $this->city->name_ar, 'name_en' => $this->city->name_en],
                'district' => ['slug' => $this->district->slug, 'name_ar' => $this->district->name_ar, 'name_en' => $this->district->name_en],
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'address_line' => $this->when($this->includePrivate, $this->address_line),
            ],
            'area_sqm' => (float) $this->area_sqm,
            'street_width_m' => $this->street_width_m !== null ? (float) $this->street_width_m : null,
            'facade' => $this->facade?->value,
            'rooms' => [
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'living_rooms' => $this->living_rooms,
            ],
            'building' => [
                'floor_number' => $this->floor_number,
                'total_floors' => $this->total_floors,
                'building_age_years' => $this->building_age_years,
                'furnishing' => $this->furnishing?->value,
                'condition' => $this->property_condition?->value,
            ],
            'price' => [
                'amount' => $this->price,
                'currency' => $this->currency,
                'per_sqm' => $this->price_per_sqm,
            ],
            'features' => $this->features->map(fn (Feature $feature): array => [
                'slug' => $feature->slug,
                'name_ar' => $feature->name_ar,
                'name_en' => $feature->name_en,
                'group' => $feature->group->value,
            ])->values()->all(),
            'ad_license_number' => $this->ad_license_number,
            'verification_status' => $this->verification_status->value,
            'advertiser' => [
                'id' => $this->advertiser->public_id,
                'name' => $this->advertiser->name,
            ],
            'published_at' => $this->published_at?->toIso8601ZuluString(),
            'expires_at' => $this->expires_at?->toIso8601ZuluString(),
            // Managers only:
            'submitted_at' => $this->when($this->includePrivate, fn () => $this->submitted_at?->toIso8601ZuluString()),
            'moderated_at' => $this->when($this->includePrivate, fn () => $this->moderated_at?->toIso8601ZuluString()),
            'created_at' => $this->when($this->includePrivate, fn () => $this->created_at?->toIso8601ZuluString()),
            'updated_at' => $this->when($this->includePrivate, fn () => $this->updated_at?->toIso8601ZuluString()),
        ];
    }
}
