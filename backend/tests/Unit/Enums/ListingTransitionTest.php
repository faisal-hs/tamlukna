<?php

namespace Tests\Unit\Enums;

use App\Enums\ListingStatus;
use App\Enums\ListingTransition;
use PHPUnit\Framework\TestCase;

final class ListingTransitionTest extends TestCase
{
    public function test_only_pending_listings_can_be_approved(): void
    {
        foreach (ListingStatus::cases() as $status) {
            $this->assertSame(
                $status === ListingStatus::PendingReview,
                ListingTransition::Approve->allowsFrom($status),
                "approve from {$status->value}",
            );
        }
    }

    public function test_no_transition_targets_one_of_its_own_source_states(): void
    {
        foreach (ListingTransition::cases() as $transition) {
            $this->assertNotContains($transition->target(), $transition->sources(), $transition->value);
        }
    }

    public function test_only_reject_requires_a_reason(): void
    {
        foreach (ListingTransition::cases() as $transition) {
            $this->assertSame($transition === ListingTransition::Reject, $transition->requiresReason());
        }
    }
}
