<?php

namespace Tests\Feature\Database;

use App\Models\Feature;
use App\Models\PropertyType;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_types_declare_which_attributes_apply(): void
    {
        $this->seed(CatalogSeeder::class);

        $apartment = PropertyType::query()->where('slug', 'apartment')->firstOrFail();
        $this->assertTrue($apartment->allows_rooms);
        $this->assertTrue($apartment->allows_building_details);

        foreach (['residential-land', 'commercial-land', 'farm'] as $slug) {
            $land = PropertyType::query()->where('slug', $slug)->firstOrFail();
            $this->assertFalse($land->allows_rooms, "{$slug} must not allow rooms");
            $this->assertFalse($land->allows_building_details, "{$slug} must not allow building details");
        }

        $this->assertSame(11, PropertyType::query()->count());
        $this->assertSame(18, Feature::query()->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(11, PropertyType::query()->count());
        $this->assertSame(18, Feature::query()->count());
    }
}
