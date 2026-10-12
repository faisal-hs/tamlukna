<?php

namespace App\Policies;

use App\Enums\ListingStatus;
use App\Enums\Permission;
use App\Models\Property;
use App\Models\User;

/**
 * Listing authorization = permission + ownership. Admin access comes from the
 * explicit "manage-any" permission, not from a role check.
 */
final class PropertyPolicy
{
    /**
     * Public visitors see published, unexpired listings; everything else only
     * the managing advertiser and staff who may view unpublished listings.
     */
    public function view(?User $user, Property $property): bool
    {
        $publiclyVisible = $property->status === ListingStatus::Published
            && ($property->expires_at === null || $property->expires_at->isFuture());

        if ($publiclyVisible) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $this->manages($user, $property)
            || $user->can(Permission::PropertiesViewUnpublished->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PropertiesCreate->value);
    }

    public function update(User $user, Property $property): bool
    {
        return $this->manages($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->manages($user, $property);
    }

    /**
     * Advertiser-side transitions: submit, pause, resume.
     */
    public function changeStatus(User $user, Property $property): bool
    {
        return $this->manages($user, $property);
    }

    /**
     * Approve / reject. Nobody moderates their own listing — not even an admin.
     */
    public function moderate(User $user, Property $property): bool
    {
        return $user->can(Permission::PropertiesModerate->value)
            && $property->advertiser_id !== $user->id;
    }

    public function viewHistory(User $user, Property $property): bool
    {
        return $this->manages($user, $property)
            || $user->can(Permission::PropertiesViewUnpublished->value);
    }

    private function manages(User $user, Property $property): bool
    {
        if ($user->can(Permission::PropertiesManageAny->value)) {
            return true;
        }

        return $property->advertiser_id === $user->id
            && $user->can(Permission::PropertiesManageOwn->value);
    }
}
