<?php

namespace Database\Factories;

use App\Enums\Facade;
use App\Enums\Furnishing;
use App\Enums\ListingStatus;
use App\Enums\PropertyCondition;
use App\Enums\PropertyPurpose;
use App\Enums\RentPeriod;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\City;
use App\Models\District;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test/sample listings only. Every title is marked "[تجريبي]" / "[Sample]".
 *
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(10000, 99999);

        return [
            'advertiser_id' => User::factory()->withRole(Role::Owner),
            'property_type_id' => PropertyType::factory(),
            'purpose' => PropertyPurpose::Sale,
            'rent_period' => null,
            'property_condition' => PropertyCondition::Used,
            'status' => ListingStatus::Draft,
            'verification_status' => VerificationStatus::Unverified,
            'title_ar' => "[تجريبي] عقار اختبار {$n}",
            'title_en' => "[Sample] Test property {$n}",
            'description_ar' => 'بيانات تجريبية لأغراض التطوير والاختبار فقط، ولا تمثل عقارًا حقيقيًا.',
            'description_en' => 'Sample data for development and testing only; not a real property.',
            'city_id' => City::factory(),
            'district_id' => fn (array $attributes) => District::factory()->create(['city_id' => $attributes['city_id']])->id,
            'address_line' => null,
            'latitude' => fake()->randomFloat(7, 24.60, 24.90),
            'longitude' => fake()->randomFloat(7, 46.50, 46.90),
            'area_sqm' => fake()->numberBetween(80, 1500),
            'street_width_m' => fake()->randomElement([12, 15, 20, 30, 40]),
            'facade' => fake()->randomElement(Facade::cases()),
            'bedrooms' => fake()->numberBetween(1, 6),
            'bathrooms' => fake()->numberBetween(1, 5),
            'living_rooms' => fake()->numberBetween(1, 3),
            'floor_number' => fake()->numberBetween(0, 7),
            'total_floors' => fake()->numberBetween(8, 12),
            'building_age_years' => fake()->numberBetween(0, 25),
            'furnishing' => Furnishing::Unfurnished,
            'price' => fake()->numberBetween(300, 5000) * 1000,
            'currency' => 'SAR',
            'ad_license_number' => null,
        ];
    }

    /**
     * Uses the type and clears attributes that do not apply to it.
     */
    public function forType(PropertyType $type): static
    {
        return $this->state(fn (array $attributes) => array_merge(
            ['property_type_id' => $type->id],
            $type->allows_rooms ? [] : ['bedrooms' => null, 'bathrooms' => null, 'living_rooms' => null],
            $type->allows_building_details ? [] : [
                'floor_number' => null,
                'total_floors' => null,
                'building_age_years' => null,
                'furnishing' => null,
                'property_condition' => null,
            ],
        ));
    }

    public function forRent(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => PropertyPurpose::Rent,
            'rent_period' => RentPeriod::Yearly,
            'price' => fake()->numberBetween(20, 250) * 1000,
        ]);
    }

    public function at(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => ['latitude' => $latitude, 'longitude' => $longitude]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::PendingReview,
            'submitted_at' => now()->subHours(3),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Published,
            'submitted_at' => now()->subDays(2),
            'moderated_at' => now()->subDay(),
            'published_at' => now()->subDay(),
            'expires_at' => now()->addDays(90),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Rejected,
            'submitted_at' => now()->subDays(2),
            'moderated_at' => now()->subDay(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Paused,
            'submitted_at' => now()->subDays(10),
            'moderated_at' => now()->subDays(9),
            'published_at' => now()->subDays(9),
            'expires_at' => now()->addDays(80),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Expired,
            'submitted_at' => now()->subDays(100),
            'moderated_at' => now()->subDays(99),
            'published_at' => now()->subDays(99),
            'expires_at' => now()->subDays(9),
        ]);
    }
}
