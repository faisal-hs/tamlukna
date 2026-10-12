<?php

namespace App\Services\Properties;

use App\Enums\ListingStatus;
use App\Exceptions\Api\ListingNotEditableException;
use App\Models\Property;
use App\Models\PropertyStatusHistory;
use App\Models\PropertyType;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Listing content: create, edit, delete. Status changes go through ListingWorkflow.
 * Only fillable content fields are ever taken from input; fields that do not
 * apply to the property type are cleared. Authorization is the caller's job.
 */
final class PropertyService
{
    private const ROOM_FIELDS = ['bedrooms', 'bathrooms', 'living_rooms'];

    private const BUILDING_FIELDS = ['floor_number', 'total_floors', 'building_age_years', 'furnishing', 'property_condition'];

    public function __construct(
        private readonly ListingWorkflow $workflow,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated content
     * @param  list<int>|null  $featureIds
     */
    public function create(User $advertiser, array $data, ?array $featureIds = null, ?Request $request = null): Property
    {
        return DB::transaction(function () use ($advertiser, $data, $featureIds, $request): Property {
            $type = PropertyType::query()->findOrFail($data['property_type_id'] ?? null);

            $property = new Property;
            $property->fill($this->contentOnly($data));
            $property->forceFill($this->clearedFieldsFor($type));
            $property->forceFill(['advertiser_id' => $advertiser->id, 'status' => ListingStatus::Draft]);
            $property->save();

            if ($featureIds !== null) {
                $property->features()->sync($featureIds);
            }

            PropertyStatusHistory::create([
                'property_id' => $property->id,
                'from_status' => null,
                'to_status' => ListingStatus::Draft,
                'actor_id' => $advertiser->id,
            ]);

            $this->audit->record('property.created', $property, [], $advertiser, $request);

            return $property->refresh();
        });
    }

    /**
     * Editing a published or paused listing sends it back to review.
     *
     * @param  array<string, mixed>  $data  Validated content (partial updates allowed)
     * @param  list<int>|null  $featureIds  null = leave features unchanged
     */
    public function update(Property $property, array $data, ?array $featureIds, User $actor, ?Request $request = null): Property
    {
        return DB::transaction(function () use ($property, $data, $featureIds, $actor, $request): Property {
            $locked = Property::query()->whereKey($property->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === ListingStatus::PendingReview) {
                throw new ListingNotEditableException;
            }

            $locked->fill($this->contentOnly($data));
            $type = PropertyType::query()->findOrFail($locked->property_type_id);
            $locked->forceFill($this->clearedFieldsFor($type));

            $changedFields = array_keys($locked->getDirty());
            $locked->save();

            if ($featureIds !== null) {
                $sync = $locked->features()->sync($featureIds);
                if ($sync['attached'] !== [] || $sync['detached'] !== []) {
                    $changedFields[] = 'features';
                }
            }

            if ($changedFields === []) {
                return $locked;
            }

            $this->audit->record('property.updated', $locked, ['fields' => $changedFields], $actor, $request);

            if (in_array($locked->status, [ListingStatus::Published, ListingStatus::Paused], true)) {
                $this->workflow->revise($locked, $actor, $request);
            }

            return $locked->refresh();
        });
    }

    public function delete(Property $property, User $actor, ?Request $request = null): void
    {
        DB::transaction(function () use ($property, $actor, $request): void {
            $property->delete();

            $this->audit->record('property.deleted', $property, ['status' => $property->status->value], $actor, $request);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contentOnly(array $data): array
    {
        return Arr::only($data, (new Property)->getFillable());
    }

    /**
     * @return array<string, null>
     */
    private function clearedFieldsFor(PropertyType $type): array
    {
        $fields = array_merge(
            $type->allows_rooms ? [] : self::ROOM_FIELDS,
            $type->allows_building_details ? [] : self::BUILDING_FIELDS,
        );

        return array_fill_keys($fields, null);
    }
}
