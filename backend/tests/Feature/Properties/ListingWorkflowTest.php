<?php

namespace Tests\Feature\Properties;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Exceptions\Api\EmailNotVerifiedException;
use App\Exceptions\Api\InvalidListingTransitionException;
use App\Models\Property;
use App\Models\User;
use App\Services\Properties\ListingWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ListingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function workflow(): ListingWorkflow
    {
        return app(ListingWorkflow::class);
    }

    private function advertiserOf(Property $property): User
    {
        return User::query()->findOrFail($property->advertiser_id);
    }

    private function moderator(): User
    {
        return User::factory()->withRole(Role::Staff)->create();
    }

    public function test_submitting_a_complete_draft_moves_it_to_review_with_history_and_audit(): void
    {
        $property = Property::factory()->create();
        $advertiser = $this->advertiserOf($property);

        $result = $this->workflow()->submit($property, $advertiser);

        $this->assertSame(ListingStatus::PendingReview, $result->status);
        $this->assertNotNull($result->submitted_at);
        $this->assertDatabaseHas('property_status_histories', [
            'property_id' => $property->id,
            'from_status' => 'draft',
            'to_status' => 'pending_review',
            'actor_id' => $advertiser->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'property.status_changed',
            'auditable_type' => 'property',
            'auditable_id' => $property->id,
        ]);
    }

    public function test_submission_requires_a_verified_email(): void
    {
        $property = Property::factory()
            ->state(['advertiser_id' => User::factory()->unverified()->withRole(Role::Owner)])
            ->create();

        $this->expectException(EmailNotVerifiedException::class);
        $this->workflow()->submit($property, $this->advertiserOf($property));
    }

    public function test_incomplete_listings_cannot_be_submitted(): void
    {
        $property = Property::factory()->create(['description_ar' => null, 'bedrooms' => null]);

        try {
            $this->workflow()->submit($property, $this->advertiserOf($property));
            $this->fail('Expected a validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('description_ar', $e->errors());
            $this->assertArrayHasKey('bedrooms', $e->errors());
        }

        $this->assertSame(ListingStatus::Draft, $property->refresh()->status);
    }

    public function test_approval_publishes_with_publication_and_expiry_dates(): void
    {
        $property = Property::factory()->pendingReview()->create();
        $moderator = $this->moderator();

        $result = $this->workflow()->approve($property, $moderator);

        $this->assertSame(ListingStatus::Published, $result->status);
        $this->assertNotNull($result->published_at);
        $this->assertNotNull($result->moderated_at);
        $this->assertSame(90, (int) round($result->published_at->diffInDays($result->expires_at)));
        $this->assertDatabaseHas('property_status_histories', [
            'property_id' => $property->id,
            'to_status' => 'published',
            'actor_id' => $moderator->id,
        ]);
    }

    public function test_rejection_requires_a_reason_and_records_it(): void
    {
        $property = Property::factory()->pendingReview()->create();
        $moderator = $this->moderator();

        $this->assertThrows(fn () => $this->workflow()->reject($property, $moderator, ''), ValidationException::class);

        $result = $this->workflow()->reject($property, $moderator, 'Photos do not match the description.');

        $this->assertSame(ListingStatus::Rejected, $result->status);
        $this->assertDatabaseHas('property_status_histories', [
            'property_id' => $property->id,
            'to_status' => 'rejected',
            'reason' => 'Photos do not match the description.',
        ]);
    }

    public function test_transitions_from_wrong_states_are_refused(): void
    {
        $moderator = $this->moderator();
        $draft = Property::factory()->create();
        $published = Property::factory()->published()->create();
        $pending = Property::factory()->pendingReview()->create();

        $this->assertThrows(fn () => $this->workflow()->approve($draft, $moderator), InvalidListingTransitionException::class);
        $this->assertThrows(fn () => $this->workflow()->pause($draft, $this->advertiserOf($draft)), InvalidListingTransitionException::class);
        $this->assertThrows(fn () => $this->workflow()->resume($published, $this->advertiserOf($published)), InvalidListingTransitionException::class);
        $this->assertThrows(fn () => $this->workflow()->submit($pending, $this->advertiserOf($pending)), InvalidListingTransitionException::class);

        $this->assertSame(ListingStatus::Draft, $draft->refresh()->status);
    }

    public function test_pause_and_resume_but_not_after_the_expiry_date(): void
    {
        $property = Property::factory()->published()->create();
        $advertiser = $this->advertiserOf($property);

        $this->assertSame(ListingStatus::Paused, $this->workflow()->pause($property, $advertiser)->status);
        $this->assertSame(ListingStatus::Published, $this->workflow()->resume($property, $advertiser)->status);

        $this->workflow()->pause($property, $advertiser);
        $property->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->assertThrows(fn () => $this->workflow()->resume($property, $advertiser), InvalidListingTransitionException::class);
    }

    public function test_expiry_command_expires_only_overdue_published_or_paused_listings(): void
    {
        $overduePublished = Property::factory()->published()->create(['expires_at' => now()->subHour()]);
        $overduePaused = Property::factory()->paused()->create(['expires_at' => now()->subHour()]);
        $current = Property::factory()->published()->create();
        $draft = Property::factory()->create(['expires_at' => now()->subHour()]);

        $this->artisan('listings:expire')->expectsOutput('Expired 2 listing(s).')->assertSuccessful();
        $this->artisan('listings:expire')->expectsOutput('Expired 0 listing(s).')->assertSuccessful();

        $this->assertSame(ListingStatus::Expired, $overduePublished->refresh()->status);
        $this->assertSame(ListingStatus::Expired, $overduePaused->refresh()->status);
        $this->assertSame(ListingStatus::Published, $current->refresh()->status);
        $this->assertSame(ListingStatus::Draft, $draft->refresh()->status);
    }
}
