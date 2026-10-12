<?php

namespace Tests\Feature\Properties;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Exceptions\Api\ListingNotEditableException;
use App\Models\District;
use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Services\Properties\PropertyService;
use App\Support\Properties\PropertyReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PropertyServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PropertyService
    {
        return app(PropertyService::class);
    }

    public function test_create_makes_a_draft_ignores_lifecycle_fields_and_clears_inapplicable_attributes(): void
    {
        $owner = User::factory()->withRole(Role::Owner)->create();
        $land = PropertyType::factory()->land()->create();
        $district = District::factory()->create();
        $feature = Feature::factory()->create();

        $property = $this->service()->create($owner, [
            'property_type_id' => $land->id,
            'purpose' => 'sale',
            'title_ar' => 'أرض سكنية للبيع',
            'city_id' => $district->city_id,
            'district_id' => $district->id,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'area_sqm' => 600,
            'price' => 900000,
            'bedrooms' => 3,            // not applicable to land → cleared
            'floor_number' => 2,        // not applicable to land → cleared
            'status' => 'published',    // never taken from input
            'advertiser_id' => 999999,  // never taken from input
        ], [$feature->id]);

        $this->assertSame(ListingStatus::Draft, $property->status);
        $this->assertSame($owner->id, $property->advertiser_id);
        $this->assertNull($property->bedrooms);
        $this->assertNull($property->floor_number);
        $this->assertTrue(PropertyReference::isValid($property->reference));
        $this->assertSame([$feature->id], $property->features()->pluck('features.id')->all());
        $this->assertDatabaseHas('property_status_histories', ['property_id' => $property->id, 'from_status' => null, 'to_status' => 'draft']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property.created', 'auditable_id' => $property->id]);
    }

    public function test_editing_a_published_listing_sends_it_back_to_review(): void
    {
        $property = Property::factory()->published()->create();
        $owner = User::query()->findOrFail($property->advertiser_id);

        $updated = $this->service()->update($property, ['price' => 1234567], null, $owner);

        $this->assertSame(ListingStatus::PendingReview, $updated->status);
        $this->assertSame('1234567.00', $updated->price);
        $this->assertDatabaseHas('property_status_histories', [
            'property_id' => $property->id,
            'from_status' => 'published',
            'to_status' => 'pending_review',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property.updated', 'auditable_id' => $property->id]);
    }

    public function test_an_update_without_changes_keeps_the_listing_published(): void
    {
        $property = Property::factory()->published()->create(['price' => 500000]);
        $owner = User::query()->findOrFail($property->advertiser_id);

        $result = $this->service()->update($property, ['price' => 500000], null, $owner);

        $this->assertSame(ListingStatus::Published, $result->status);
    }

    public function test_listings_under_review_cannot_be_edited(): void
    {
        $property = Property::factory()->pendingReview()->create();

        $this->expectException(ListingNotEditableException::class);
        $this->service()->update($property, ['price' => 1], null, User::query()->findOrFail($property->advertiser_id));
    }

    public function test_delete_soft_deletes_and_audits(): void
    {
        $property = Property::factory()->published()->create();

        $this->service()->delete($property, User::query()->findOrFail($property->advertiser_id));

        $this->assertSoftDeleted($property);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property.deleted', 'auditable_id' => $property->id]);
    }
}
