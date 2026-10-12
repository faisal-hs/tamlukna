<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Metadata only; no file is written to storage.
 *
 * @extends Factory<PropertyMedia>
 */
class PropertyMediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'disk' => 'local',
            'path' => 'properties/test/'.Str::uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(80_000, 4_000_000),
            'width' => 1600,
            'height' => 1200,
            'checksum_sha256' => hash('sha256', Str::random(32)),
            'sort_order' => 0,
            'is_cover' => false,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes) => ['is_cover' => true]);
    }
}
