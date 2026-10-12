<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Enums\Role;
use App\Models\District;
use App\Models\Feature;
use App\Models\PropertyType;
use App\Models\User;
use App\Support\Properties\PropertyReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class CreatePropertyTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_owner_creates_a_draft_and_lifecycle_fields_are_ignored(): void
    {
        $owner = $this->owner();
        $feature = Feature::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/properties', $this->payload([
            'features' => [$feature->id],
            'status' => 'published',
            'advertiser_id' => 999999,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.advertiser.id', $owner->public_id)
            ->assertJsonPath('data.location.address_line', 'Test street 1')
            ->assertJsonPath('data.features.0.slug', $feature->slug)
            ->assertJsonPath('data.price.amount', '950000.00')
            ->assertJsonPath('data.price.per_sqm', '5277.78')
            ->assertJsonMissingPath('data.id');

        $this->assertTrue(PropertyReference::isValid((string) $response->json('data.reference')));
    }

    public function test_users_without_permission_get_403_before_validation(): void
    {
        Sanctum::actingAs(User::factory()->withRole(Role::Buyer)->create());

        $this->postJson('/api/v1/properties', [])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_type_city_purpose_and_location_rules(): void
    {
        Sanctum::actingAs($this->owner());
        $url = '/api/v1/properties';

        $this->postJson($url, $this->payload(['district_id' => District::factory()->create()->id]))
            ->assertStatus(422)->assertJsonValidationErrors(['district_id']);

        $this->postJson($url, $this->payload(['property_type_id' => PropertyType::factory()->land()->create()->id]))
            ->assertStatus(422)->assertJsonValidationErrors(['bedrooms', 'bathrooms']);

        $this->postJson($url, $this->payload(['latitude' => 40.0]))
            ->assertStatus(422)->assertJsonValidationErrors(['latitude']);

        $this->postJson($url, $this->payload(['purpose' => 'rent']))
            ->assertStatus(422)->assertJsonValidationErrors(['rent_period']);

        $this->postJson($url, $this->payload(['rent_period' => 'yearly']))
            ->assertStatus(422)->assertJsonValidationErrors(['rent_period']);
    }
}
