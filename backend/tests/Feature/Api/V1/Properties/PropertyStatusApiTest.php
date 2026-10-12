<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Enums\Role;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class PropertyStatusApiTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_submit_moves_a_draft_to_review_and_cannot_be_repeated(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($this->advertiserOf($property));

        $this->postJson($this->url($property, '/submit'))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $this->postJson($this->url($property, '/submit'))
            ->assertStatus(409)
            ->assertJsonPath('code', 'invalid_status_transition');
    }

    public function test_incomplete_listings_cannot_be_submitted(): void
    {
        $property = Property::factory()->create(['description_ar' => null]);
        Sanctum::actingAs($this->advertiserOf($property));

        $this->postJson($this->url($property, '/submit'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description_ar']);
    }

    public function test_unverified_advertisers_cannot_submit(): void
    {
        $property = Property::factory()
            ->state(['advertiser_id' => User::factory()->unverified()->withRole(Role::Owner)])
            ->create();
        Sanctum::actingAs($this->advertiserOf($property));

        $this->postJson($this->url($property, '/submit'))
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_pause_and_resume_only_by_the_advertiser(): void
    {
        $property = Property::factory()->published()->create();

        Sanctum::actingAs(User::factory()->withRole(Role::Buyer)->create());
        $this->postJson($this->url($property, '/pause'))->assertForbidden();

        Sanctum::actingAs($this->advertiserOf($property));
        $this->postJson($this->url($property, '/pause'))->assertOk()->assertJsonPath('data.status', 'paused');
        $this->postJson($this->url($property, '/resume'))->assertOk()->assertJsonPath('data.status', 'published');
    }
}
