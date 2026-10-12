<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Models\City;
use App\Models\Feature;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class UpdatePropertyTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_advertiser_updates_a_draft_partially(): void
    {
        $property = Property::factory()->create(['title_ar' => 'عنوان أصلي للإعلان']);
        $property->features()->attach(Feature::factory()->create()->id);
        Sanctum::actingAs($this->advertiserOf($property));

        $this->patchJson($this->url($property), ['price' => 1000000, 'features' => []])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.price.amount', '1000000.00')
            ->assertJsonPath('data.title.ar', 'عنوان أصلي للإعلان')
            ->assertJsonPath('data.features', []);
    }

    public function test_hidden_listings_return_404_and_visible_ones_403_for_non_managers(): void
    {
        $draft = Property::factory()->create();
        $published = Property::factory()->published()->create();
        Sanctum::actingAs($this->owner());

        $this->patchJson($this->url($draft), ['price' => 1])->assertNotFound();
        $this->patchJson($this->url($published), ['price' => 1])->assertForbidden();
    }

    public function test_editing_a_published_listing_returns_it_to_review(): void
    {
        $property = Property::factory()->published()->create();
        Sanctum::actingAs($this->advertiserOf($property));

        $this->patchJson($this->url($property), ['price' => 777000])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');
    }

    public function test_listings_under_review_cannot_be_edited(): void
    {
        $property = Property::factory()->pendingReview()->create();
        Sanctum::actingAs($this->advertiserOf($property));

        $this->patchJson($this->url($property), ['price' => 1])
            ->assertStatus(409)
            ->assertJsonPath('code', 'listing_not_editable');
    }

    public function test_changing_the_city_requires_a_district_in_that_city(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($this->advertiserOf($property));

        $this->patchJson($this->url($property), ['city_id' => City::factory()->create()->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['district_id']);
    }
}
