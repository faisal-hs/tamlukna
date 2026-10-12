<?php

namespace Database\Factories;

use App\Enums\FeatureGroup;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feature>
 */
class FeatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1000, 9999);

        return [
            'slug' => 'test-feature-'.$n,
            'name_ar' => 'ميزة اختبار '.$n,
            'name_en' => 'Test Feature '.$n,
            'group' => FeatureGroup::Interior,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
