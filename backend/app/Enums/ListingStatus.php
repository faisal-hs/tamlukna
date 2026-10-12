<?php

namespace App\Enums;

/**
 * Listing lifecycle states. Allowed transitions are enforced in Step 8.
 */
enum ListingStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Paused = 'paused';
    case Expired = 'expired';
}
