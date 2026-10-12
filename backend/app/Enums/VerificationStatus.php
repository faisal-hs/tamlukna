<?php

namespace App\Enums;

/**
 * Platform-side verification state. "verified" may only be set from an
 * authorized, verifiable source — never claimed by default.
 */
enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
