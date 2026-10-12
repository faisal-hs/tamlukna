<?php

namespace Tests\Feature\Database;

use App\Enums\ListingStatus;
use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyMedia;
use App\Models\PropertyStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Properties\PropertyReference;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

final class PropertySchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{lat: float, lng: float}
     */
    private function point(Property $property): array
    {
        $row = DB::table('properties')->where('id', $property->id)
            ->selectRaw('ST_Latitude(location) AS lat, ST_Longitude(location) AS lng')
            ->first();

        return ['lat' => (float) $row->lat, 'lng' => (float) $row->lng];
    }

    private function assertRejected(callable $write): void
    {
        try {
            $write();
        } catch (QueryException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Expected the database to reject the write.');
    }

    public function test_creating_a_property_generates_reference_location_and_price_per_sqm(): void
    {
        $property = Property::factory()->at(24.7136, 46.6753)->create(['price' => 1500000, 'area_sqm' => 300]);

        $this->assertTrue(PropertyReference::isValid($property->reference));

        $property->refresh();
        $this->assertSame('5000.00', $property->price_per_sqm);

        $point = $this->point($property);
        $this->assertEqualsWithDelta(24.7136, $point['lat'], 0.0000001);
        $this->assertEqualsWithDelta(46.6753, $point['lng'], 0.0000001);
    }

    public function test_location_follows_coordinate_changes(): void
    {
        $property = Property::factory()->at(24.7136, 46.6753)->create();

        $property->update(['latitude' => 21.4858, 'longitude' => 39.1925]);

        $point = $this->point($property);
        $this->assertEqualsWithDelta(21.4858, $point['lat'], 0.0000001);
        $this->assertEqualsWithDelta(39.1925, $point['lng'], 0.0000001);
    }

    public function test_radius_queries_use_true_distances_and_a_spatial_index_exists(): void
    {
        $riyadh = Property::factory()->at(24.7136, 46.6753)->create();
        Property::factory()->at(21.4858, 39.1925)->create(); // Jeddah, ~850 km away

        $within5km = Property::query()
            ->whereRaw('ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ?', [46.6800, 24.7200, 5000])
            ->pluck('id')
            ->all();

        $this->assertSame([$riyadh->id], $within5km);
        $this->assertNotEmpty(DB::select("SHOW INDEX FROM properties WHERE Key_name = 'properties_location_spatial'"));
    }

    public function test_database_rejects_negative_prices_and_non_positive_areas(): void
    {
        $this->assertRejected(fn () => Property::factory()->create(['price' => -1]));
        $this->assertRejected(fn () => Property::factory()->create(['area_sqm' => 0]));
    }

    public function test_only_one_cover_image_per_property(): void
    {
        $property = Property::factory()->create();
        PropertyMedia::factory()->cover()->create(['property_id' => $property->id]);
        PropertyMedia::factory()->count(2)->create(['property_id' => $property->id]);

        $this->assertRejected(fn () => PropertyMedia::factory()->cover()->create(['property_id' => $property->id]));

        $other = Property::factory()->create();
        PropertyMedia::factory()->cover()->create(['property_id' => $other->id]);
        $this->assertSame(1, PropertyMedia::query()->where('property_id', $other->id)->where('is_cover', true)->count());
    }

    public function test_properties_with_media_cannot_be_hard_deleted_directly(): void
    {
        $property = Property::factory()->create();
        PropertyMedia::factory()->create(['property_id' => $property->id]);

        $this->assertRejected(fn () => $property->forceDelete());
    }

    public function test_hard_delete_without_media_removes_features_links_and_history(): void
    {
        $property = Property::factory()->create();
        $feature = Feature::factory()->create();
        $property->features()->attach($feature->id);
        PropertyStatusHistory::create([
            'property_id' => $property->id,
            'from_status' => null,
            'to_status' => ListingStatus::Draft,
        ]);

        $property->forceDelete();

        $this->assertDatabaseMissing('property_feature', ['property_id' => $property->id]);
        $this->assertDatabaseMissing('property_status_histories', ['property_id' => $property->id]);
        $this->assertDatabaseHas('features', ['id' => $feature->id]);
    }

    public function test_advertisers_with_listings_cannot_be_deleted(): void
    {
        $property = Property::factory()->create();

        $this->assertRejected(fn () => User::query()->findOrFail($property->advertiser_id)->delete());
    }

    public function test_soft_deleted_listings_keep_their_reference_reserved(): void
    {
        $property = Property::factory()->create();
        $property->delete();

        $this->assertSoftDeleted($property);
        $this->assertRejected(fn () => Property::factory()->create(['reference' => $property->reference]));
    }

    public function test_published_scope_excludes_unpublished_and_expired_listings(): void
    {
        Property::factory()->create();
        Property::factory()->pendingReview()->create();
        Property::factory()->paused()->create();
        Property::factory()->published()->create(['expires_at' => now()->subMinute()]);
        $live = Property::factory()->published()->create();

        $this->assertSame([$live->id], Property::query()->published()->pluck('id')->all());
    }

    public function test_status_history_is_immutable(): void
    {
        $history = PropertyStatusHistory::create([
            'property_id' => Property::factory()->create()->id,
            'to_status' => ListingStatus::Draft,
        ]);

        $this->expectException(LogicException::class);
        $history->update(['reason' => 'tampered']);
    }

    public function test_audit_records_for_properties_use_the_morph_alias(): void
    {
        $property = Property::factory()->create();

        app(AuditLogger::class)->record('property.example', $property);

        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'property', 'auditable_id' => $property->id]);
    }
}
