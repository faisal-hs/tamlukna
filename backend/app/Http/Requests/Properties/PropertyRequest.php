<?php

namespace App\Http\Requests\Properties;

use App\Enums\Facade;
use App\Enums\Furnishing;
use App\Enums\PropertyCondition;
use App\Enums\PropertyPurpose;
use App\Enums\RentPeriod;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Shared listing content rules. Create = all core fields required; update =
 * every field optional, validated against the listing's current type, city
 * and purpose when those are not being changed.
 */
abstract class PropertyRequest extends FormRequest
{
    abstract protected function isPartial(): bool;

    protected function existing(): ?Property
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $partial = $this->isPartial();
        $existing = $this->existing();
        $type = $this->effectiveType();
        $purpose = $this->input('purpose', $existing?->purpose->value);
        $cityId = (int) $this->input('city_id', $existing?->city_id);
        /** @var array{min_lat: float, max_lat: float, min_lng: float, max_lng: float} $bounds */
        $bounds = config('tamlukna.geo.saudi_bounds');

        $core = fn (array $rules): array => array_merge([$partial ? 'sometimes' : 'required'], $rules);
        $noRooms = fn (): bool => $type !== null && ! $type->allows_rooms;
        $noBuilding = fn (): bool => $type !== null && ! $type->allows_building_details;

        return [
            'property_type_id' => $core(['integer', Rule::exists('property_types', 'id')->where('is_active', true)]),
            'purpose' => $core(['string', Rule::enum(PropertyPurpose::class)]),
            'rent_period' => [
                'nullable',
                Rule::enum(RentPeriod::class),
                Rule::requiredIf(fn (): bool => $purpose === PropertyPurpose::Rent->value && ($this->has('purpose') || ! $partial)),
                Rule::prohibitedIf(fn (): bool => $purpose !== PropertyPurpose::Rent->value),
            ],
            'property_condition' => ['sometimes', 'nullable', Rule::enum(PropertyCondition::class), Rule::prohibitedIf($noBuilding)],

            'title_ar' => $core(['string', 'min:5', 'max:150']),
            'title_en' => ['sometimes', 'nullable', 'string', 'max:150'],
            'description_ar' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'city_id' => $core(['integer', Rule::exists('cities', 'id')->where('is_active', true)]),
            'district_id' => array_merge(
                $partial ? ['required_with:city_id'] : ['required'],
                ['integer', Rule::exists('districts', 'id')->where('city_id', $cityId)->where('is_active', true)],
            ),
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => $core(['numeric', "between:{$bounds['min_lat']},{$bounds['max_lat']}"]),
            'longitude' => $core(['numeric', "between:{$bounds['min_lng']},{$bounds['max_lng']}"]),

            'area_sqm' => $core(['numeric', 'gt:0', 'max:10000000']),
            'street_width_m' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999'],
            'facade' => ['sometimes', 'nullable', Rule::enum(Facade::class)],

            'bedrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50', Rule::prohibitedIf($noRooms)],
            'bathrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50', Rule::prohibitedIf($noRooms)],
            'living_rooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50', Rule::prohibitedIf($noRooms)],

            'floor_number' => ['sometimes', 'nullable', 'integer', 'between:-5,100', Rule::prohibitedIf($noBuilding)],
            'total_floors' => ['sometimes', 'nullable', 'integer', 'between:1,100', Rule::prohibitedIf($noBuilding)],
            'building_age_years' => ['sometimes', 'nullable', 'integer', 'between:0,150', Rule::prohibitedIf($noBuilding)],
            'furnishing' => ['sometimes', 'nullable', Rule::enum(Furnishing::class), Rule::prohibitedIf($noBuilding)],

            'price' => $core(['numeric', 'min:0', 'max:999999999999']),
            'ad_license_number' => ['sometimes', 'nullable', 'string', 'max:50'],

            'features' => ['sometimes', 'nullable', 'array', 'max:30'],
            'features.*' => ['integer', 'distinct', Rule::exists('features', 'id')->where('is_active', true)],
        ];
    }

    /**
     * Validated listing content (without features).
     *
     * @return array<string, mixed>
     */
    public function contentData(): array
    {
        return Arr::except($this->validated(), ['features']);
    }

    /**
     * @return list<int>|null null when the client did not send "features"
     */
    public function featureIds(): ?array
    {
        if (! $this->has('features')) {
            return null;
        }

        $ids = $this->validated('features') ?? [];

        return array_values(array_map('intval', is_array($ids) ? $ids : []));
    }

    private function effectiveType(): ?PropertyType
    {
        $id = $this->input('property_type_id', $this->existing()?->property_type_id);

        return is_numeric($id) ? PropertyType::query()->find((int) $id) : null;
    }
}
