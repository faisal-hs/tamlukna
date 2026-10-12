<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(100, 999);

        return [
            'code' => 'TEST-'.$n,
            'name_ar' => 'منطقة اختبار '.$n,
            'name_en' => 'Test Region '.$n,
            'data_source' => 'factory',
        ];
    }
}
