<?php

namespace Tests\Feature\Database;

use App\Models\City;
use App\Models\District;
use App\Models\Property;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PropertyDistrictIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_district_must_belong_to_the_listing_city(): void
    {
        $city = City::factory()->create();
        $districtElsewhere = District::factory()->create();

        $this->expectException(QueryException::class);
        Property::factory()->create(['city_id' => $city->id, 'district_id' => $districtElsewhere->id]);
    }

    public function test_a_district_is_required(): void
    {
        $this->expectException(QueryException::class);
        Property::factory()->create(['district_id' => null]);
    }
}
