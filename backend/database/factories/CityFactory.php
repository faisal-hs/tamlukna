<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1000, 9999);

        return [
            'region_id' => Region::factory(),
            'slug' => 'test-city-'.$n,
            'name_ar' => 'مدينة اختبار '.$n,
            'name_en' => 'Test City '.$n,
            'latitude' => fake()->latitude(17, 31),
            'longitude' => fake()->longitude(37, 55),
            'is_active' => true,
            'data_source' => 'factory',
        ];
    }
}
