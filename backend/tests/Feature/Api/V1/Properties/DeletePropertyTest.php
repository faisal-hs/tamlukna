<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class DeletePropertyTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_advertiser_deletes_own_listing_and_others_cannot(): void
    {
        $property = Property::factory()->published()->create();

        Sanctum::actingAs($this->owner());
        $this->deleteJson($this->url($property))->assertForbidden();

        Sanctum::actingAs($this->advertiserOf($property));
        $this->deleteJson($this->url($property))->assertOk();
        $this->getJson($this->url($property))->assertNotFound();

        $this->assertSoftDeleted($property);
    }
}
