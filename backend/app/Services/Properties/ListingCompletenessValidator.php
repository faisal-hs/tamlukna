<?php

namespace App\Services\Properties;

use App\Enums\PropertyPurpose;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Rules a listing must satisfy before it can be submitted for review.
 * Drafts may be incomplete; submission may not.
 */
final class ListingCompletenessValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(Property $property): void
    {
        $type = PropertyType::query()->findOrFail($property->property_type_id);
        /** @var array{min_lat: float, max_lat: float, min_lng: float, max_lng: float} $bounds */
        $bounds = config('tamlukna.geo.saudi_bounds');

        $rules = [
            'title_ar' => ['required', 'string', 'min:5', 'max:150'],
            'description_ar' => ['required', 'string', 'min:30', 'max:5000'],
            'district_id' => ['required', 'integer'],
            'price' => ['required', 'numeric', 'gt:0'],
            'area_sqm' => ['required', 'numeric', 'gt:0'],
            'latitude' => ['required', 'numeric', "between:{$bounds['min_lat']},{$bounds['max_lat']}"],
            'longitude' => ['required', 'numeric', "between:{$bounds['min_lng']},{$bounds['max_lng']}"],
            'rent_period' => $property->purpose === PropertyPurpose::Rent ? ['required'] : ['prohibited'],
        ];

        if ($type->allows_rooms) {
            $rules['bedrooms'] = ['required', 'integer', 'min:0'];
            $rules['bathrooms'] = ['required', 'integer', 'min:1'];
        }

        Validator::make($property->getAttributes(), $rules)->validate();
    }
}
