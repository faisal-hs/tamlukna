<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Enums\Role;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class ShowPropertyTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_guests_see_published_listings_without_private_fields(): void
    {
        $property = Property::factory()->published()->create(['address_line' => 'Private address 7']);

        $this->getJson($this->url($property))
            ->assertOk()
            ->assertJsonPath('data.reference', $property->reference)
            ->assertJsonPath('data.advertiser.name', $this->advertiserOf($property)->name)
            ->assertJsonMissingPath('data.advertiser.email')
            ->assertJsonMissingPath('data.location.address_line')
            ->assertJsonMissingPath('data.submitted_at')
            ->assertDontSee('Private address 7');
    }

    public function test_unpublished_listings_are_visible_only_to_managers_and_staff(): void
    {
        $draft = Property::factory()->create();

        $this->getJson($this->url($draft))->assertNotFound();

        Sanctum::actingAs($this->owner());
        $this->getJson($this->url($draft))->assertNotFound();

        Sanctum::actingAs($this->advertiserOf($draft));
        $this->getJson($this->url($draft))
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonStructure(['data' => ['submitted_at', 'created_at', 'location' => ['address_line']]]);

        Sanctum::actingAs(User::factory()->withRole(Role::Staff)->create());
        $this->getJson($this->url($draft))
            ->assertOk()
            ->assertJsonMissingPath('data.location.address_line');
    }

    public function test_unknown_and_malformed_references_return_404(): void
    {
        $this->getJson('/api/v1/properties/TML-ZZZZZZZZ')->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->getJson('/api/v1/properties/not-a-reference')->assertNotFound()->assertJsonPath('code', 'not_found');
    }
}
