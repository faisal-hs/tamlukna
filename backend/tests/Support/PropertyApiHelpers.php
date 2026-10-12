<?php

namespace Tests\Support;

use App\Enums\Role;
use App\Models\District;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;

trait PropertyApiHelpers
{
    protected function owner(): User
    {
        return User::factory()->withRole(Role::Owner)->create();
    }

    protected function advertiserOf(Property $property): User
    {
        return User::query()->findOrFail($property->advertiser_id);
    }

    protected function url(Property $property, string $suffix = ''): string
    {
        return "/api/v1/properties/{$property->reference}{$suffix}";
    }

    /**
     * A complete, valid listing payload (new district and room-type each call).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        $district = District::factory()->create();
        $type = PropertyType::factory()->create();

        return array_merge([
            'property_type_id' => $type->id,
            'purpose' => 'sale',
            'title_ar' => 'شقة تجريبية للبيع',
            'description_ar' => 'وصف تجريبي كافٍ لاجتياز شرط الحد الأدنى لطول الوصف في الإعلان.',
            'city_id' => $district->city_id,
            'district_id' => $district->id,
            'address_line' => 'Test street 1',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'area_sqm' => 180,
            'price' => 950000,
            'bedrooms' => 3,
            'bathrooms' => 2,
        ], $overrides);
    }
}
