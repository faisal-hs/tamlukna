<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class MyPropertiesApiTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_lists_own_listings_with_filters_sorting_and_pagination(): void
    {
        $owner = $this->owner();
        Property::factory()->create(['advertiser_id' => $owner->id, 'price' => 300000]);
        $cheaper = Property::factory()->published()->create(['advertiser_id' => $owner->id, 'price' => 200000]);
        Property::factory()->create(['advertiser_id' => $owner->id])->delete();
        Property::factory()->published()->create(); // someone else's

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/me/properties?sort=price')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.reference', $cheaper->reference)
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonStructure(['data' => [['location' => ['address_line'], 'created_at']]]);

        $this->getJson('/api/v1/me/properties?status=published')->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/me/properties?sort=password')->assertStatus(422)->assertJsonValidationErrors(['sort']);
        $this->getJson('/api/v1/me/properties?status=sold')->assertStatus(422)->assertJsonValidationErrors(['status']);
    }
}
