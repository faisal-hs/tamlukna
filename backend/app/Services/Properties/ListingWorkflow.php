<?php

namespace App\Services\Properties;

use App\Enums\ListingTransition;
use App\Exceptions\Api\EmailNotVerifiedException;
use App\Exceptions\Api\InvalidListingTransitionException;
use App\Models\Property;
use App\Models\PropertyStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * The single place where a listing's status changes. Each transition locks the
 * row, validates the current status, updates timestamps, and records history
 * and an audit entry — atomically. Authorization is the caller's job (policy).
 */
final class ListingWorkflow
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ListingCompletenessValidator $completeness,
    ) {}

    public function submit(Property $property, User $actor, ?Request $request = null): Property
    {
        if (! $actor->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException;
        }

        return $this->apply($property, ListingTransition::Submit, $actor, null, $request);
    }

    public function approve(Property $property, User $moderator, ?Request $request = null): Property
    {
        return $this->apply($property, ListingTransition::Approve, $moderator, null, $request);
    }

    public function reject(Property $property, User $moderator, string $reason, ?Request $request = null): Property
    {
        return $this->apply($property, ListingTransition::Reject, $moderator, $reason, $request);
    }

    public function pause(Property $property, User $actor, ?Request $request = null): Property
    {
        return $this->apply($property, ListingTransition::Pause, $actor, null, $request);
    }

    public function resume(Property $property, User $actor, ?Request $request = null): Property
    {
        return $this->apply($property, ListingTransition::Resume, $actor, null, $request);
    }

    public function revise(Property $property, User $actor, ?Request $request = null): Property
    {
        return $this->apply($property, ListingTransition::Revise, $actor, null, $request);
    }

    public function expire(Property $property): Property
    {
        return $this->apply($property, ListingTransition::Expire, null, null, null, 'system');
    }

    public function apply(
        Property $property,
        ListingTransition $transition,
        ?User $actor,
        ?string $reason = null,
        ?Request $request = null,
        ?string $source = null,
    ): Property {
        if ($transition->requiresReason()) {
            Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:5', 'max:1000']])->validate();
        }

        return DB::transaction(function () use ($property, $transition, $actor, $reason, $request, $source): Property {
            $locked = Property::query()->whereKey($property->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $transition->allowsFrom($from)) {
                throw new InvalidListingTransitionException;
            }

            if ($transition === ListingTransition::Resume && $locked->expires_at?->isPast()) {
                throw new InvalidListingTransitionException;
            }

            if ($transition === ListingTransition::Submit) {
                $this->completeness->validate($locked);
            }

            $now = now();
            $changes = ['status' => $transition->target()];

            if (in_array($transition, [ListingTransition::Submit, ListingTransition::Revise], true)) {
                $changes['submitted_at'] = $now;
            }

            if (in_array($transition, [ListingTransition::Approve, ListingTransition::Reject], true)) {
                $changes['moderated_at'] = $now;
            }

            if ($transition === ListingTransition::Approve) {
                $changes['published_at'] = $now;
                $changes['expires_at'] = $now->copy()->addDays((int) config('tamlukna.listings.duration_days', 90));
            }

            $locked->forceFill($changes)->save();

            PropertyStatusHistory::create([
                'property_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $transition->target(),
                'actor_id' => $actor?->id,
                'reason' => $reason,
            ]);

            $this->audit->record(
                action: 'property.status_changed',
                subject: $locked,
                changes: array_filter([
                    'transition' => $transition->value,
                    'from' => $from->value,
                    'to' => $transition->target()->value,
                    'reason' => $reason,
                ], fn ($value) => $value !== null),
                actor: $actor,
                request: $request,
                source: $source,
            );

            return $locked;
        });
    }
}
