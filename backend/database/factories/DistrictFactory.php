<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1000, 9999);

        return [
            'city_id' => City::factory(),
            'slug' => 'test-district-'.$n,
            'name_ar' => 'حي اختبار '.$n,
            'name_en' => 'Test District '.$n,
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
            'data_source' => 'factory',
        ];
    }
}
