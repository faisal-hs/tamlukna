<?php

namespace Database\Factories;

use App\Enums\PropertyCategory;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1000, 9999);

        return [
            'slug' => 'test-type-'.$n,
            'name_ar' => 'نوع اختبار '.$n,
            'name_en' => 'Test Type '.$n,
            'category' => PropertyCategory::Residential,
            'allows_rooms' => true,
            'allows_building_details' => true,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function land(): static
    {
        return $this->state(fn (array $attributes) => [
            'allows_rooms' => false,
            'allows_building_details' => false,
        ]);
    }
}
