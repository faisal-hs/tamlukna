<?php

namespace App\Enums;

/**
 * The only ways a listing's status can change. Clients request a transition by
 * name; they never send a target status. See docs/architecture/listing-lifecycle.md.
 */
enum ListingTransition: string
{
    case Submit = 'submit';     // advertiser
    case Approve = 'approve';   // moderator
    case Reject = 'reject';     // moderator, reason required
    case Pause = 'pause';       // advertiser
    case Resume = 'resume';     // advertiser
    case Revise = 'revise';     // automatic after editing a published/paused listing
    case Expire = 'expire';     // scheduler

    /**
     * @return list<ListingStatus>
     */
    public function sources(): array
    {
        return match ($this) {
            self::Submit => [ListingStatus::Draft, ListingStatus::Rejected, ListingStatus::Expired],
            self::Approve => [ListingStatus::PendingReview],
            self::Reject => [ListingStatus::PendingReview, ListingStatus::Published, ListingStatus::Paused],
            self::Pause => [ListingStatus::Published],
            self::Resume => [ListingStatus::Paused],
            self::Revise, self::Expire => [ListingStatus::Published, ListingStatus::Paused],
        };
    }

    public function target(): ListingStatus
    {
        return match ($this) {
            self::Submit, self::Revise => ListingStatus::PendingReview,
            self::Approve, self::Resume => ListingStatus::Published,
            self::Reject => ListingStatus::Rejected,
            self::Pause => ListingStatus::Paused,
            self::Expire => ListingStatus::Expired,
        };
    }

    public function allowsFrom(ListingStatus $status): bool
    {
        return in_array($status, $this->sources(), true);
    }

    public function requiresReason(): bool
    {
        return $this === self::Reject;
    }
}
