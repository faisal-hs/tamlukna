<?php

namespace Tests\Feature\Database;

use App\Models\City;
use App\Models\District;
use App\Models\Region;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LocationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_all_regions_and_labels_cities_and_districts_as_sample(): void
    {
        $this->seed(LocationSeeder::class);

        $this->assertSame(13, Region::query()->count());
        $this->assertSame(
            ['SA-01', 'SA-02', 'SA-03', 'SA-04', 'SA-05', 'SA-06', 'SA-07', 'SA-08', 'SA-09', 'SA-10', 'SA-11', 'SA-12', 'SA-14'],
            Region::query()->orderBy('code')->pluck('code')->all(),
        );

        $riyadh = City::query()->with('region')->where('slug', 'riyadh')->firstOrFail();
        $this->assertSame('SA-01', $riyadh->region->code);
        $this->assertSame('الرياض', $riyadh->name_ar);

        $this->assertSame(0, City::query()->where('data_source', '!=', 'sample')->count());
        $this->assertSame(0, District::query()->where('data_source', '!=', 'sample')->count());
        $this->assertSame(0, District::query()->whereNotNull('latitude')->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(LocationSeeder::class);
        $counts = [Region::query()->count(), City::query()->count(), District::query()->count()];

        $this->seed(LocationSeeder::class);

        $this->assertSame($counts, [Region::query()->count(), City::query()->count(), District::query()->count()]);
    }
}
