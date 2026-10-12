<?php

namespace Tests\Feature\Properties;

use App\Enums\Role;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class PropertyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_visibility(): void
    {
        $published = Property::factory()->published()->create();
        $expired = Property::factory()->published()->create(['expires_at' => now()->subMinute()]);
        $draft = Property::factory()->create();
        $advertiser = User::query()->findOrFail($draft->advertiser_id);

        $this->assertTrue(Gate::forUser(null)->allows('view', $published));
        $this->assertFalse(Gate::forUser(null)->allows('view', $expired));
        $this->assertFalse(Gate::forUser(null)->allows('view', $draft));

        $this->assertTrue(Gate::forUser($advertiser)->allows('view', $draft));
        $this->assertFalse(Gate::forUser(User::factory()->withRole(Role::Owner)->create())->allows('view', $draft));
        $this->assertTrue(Gate::forUser(User::factory()->withRole(Role::Staff)->create())->allows('view', $draft));
        $this->assertTrue(Gate::forUser(User::factory()->withRole(Role::Admin)->create())->allows('view', $draft));
    }

    public function test_management_requires_ownership_and_permission_or_manage_any(): void
    {
        $property = Property::factory()->create();
        $advertiser = User::query()->findOrFail($property->advertiser_id);

        $this->assertTrue(Gate::forUser($advertiser)->allows('update', $property));
        $this->assertFalse(Gate::forUser(User::factory()->withRole(Role::Owner)->create())->allows('update', $property));
        $this->assertFalse(Gate::forUser(User::factory()->withRole(Role::Buyer)->create())->allows('update', $property));
        $this->assertFalse(Gate::forUser(User::factory()->withRole(Role::Staff)->create())->allows('update', $property));
        $this->assertTrue(Gate::forUser(User::factory()->withRole(Role::Admin)->create())->allows('update', $property));

        $advertiser->removeRole(Role::Owner->value);
        $this->assertFalse(Gate::forUser($advertiser)->allows('update', $property));
    }

    public function test_moderation_requires_permission_and_never_applies_to_own_listings(): void
    {
        $property = Property::factory()->pendingReview()->create();
        $staff = User::factory()->withRole(Role::Staff)->create();
        $admin = User::factory()->withRole(Role::Admin)->create();

        $this->assertTrue(Gate::forUser($staff)->allows('moderate', $property));
        $this->assertFalse(Gate::forUser(User::query()->findOrFail($property->advertiser_id))->allows('moderate', $property));

        $ownListing = Property::factory()->pendingReview()->create(['advertiser_id' => $admin->id]);
        $this->assertFalse(Gate::forUser($admin)->allows('moderate', $ownListing));
    }
}
