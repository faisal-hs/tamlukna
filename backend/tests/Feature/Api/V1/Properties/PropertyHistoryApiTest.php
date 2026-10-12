<?php

namespace Tests\Feature\Api\V1\Properties;

use App\Enums\Role;
use App\Models\Property;
use App\Models\User;
use App\Services\Properties\ListingWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PropertyApiHelpers;
use Tests\TestCase;

final class PropertyHistoryApiTest extends TestCase
{
    use PropertyApiHelpers, RefreshDatabase;

    public function test_history_uses_role_labels_and_is_limited_to_managers(): void
    {
        $property = Property::factory()->create();
        $advertiser = $this->advertiserOf($property);
        $workflow = app(ListingWorkflow::class);
        $workflow->submit($property, $advertiser);
        $workflow->approve($property, User::factory()->withRole(Role::Staff)->create());

        $this->getJson($this->url($property, '/history'))->assertUnauthorized();

        Sanctum::actingAs($this->owner());
        $this->getJson($this->url($property, '/history'))->assertForbidden();

        Sanctum::actingAs($advertiser);
        $this->getJson($this->url($property, '/history'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.from', 'draft')
            ->assertJsonPath('data.0.to', 'pending_review')
            ->assertJsonPath('data.0.actor', 'advertiser')
            ->assertJsonPath('data.1.to', 'published')
            ->assertJsonPath('data.1.actor', 'moderator')
            ->assertJsonMissingPath('data.1.actor_id');
    }
}
