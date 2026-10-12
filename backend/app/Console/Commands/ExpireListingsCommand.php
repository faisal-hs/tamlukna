<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Exceptions\Api\InvalidListingTransitionException;
use App\Models\Property;
use App\Services\Properties\ListingWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Expires published/paused listings whose expires_at has passed. Scheduled hourly.
 */
final class ExpireListingsCommand extends Command
{
    protected $signature = 'listings:expire';

    protected $description = 'Expire listings past their expiry date';

    public function handle(ListingWorkflow $workflow): int
    {
        $expired = 0;

        Property::query()
            ->whereIn('status', [ListingStatus::Published, ListingStatus::Paused])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function (Collection $properties) use ($workflow, &$expired): void {
                foreach ($properties as $property) {
                    try {
                        $workflow->expire($property);
                        $expired++;
                    } catch (InvalidListingTransitionException) {
                        // Status changed concurrently; nothing to do.
                    }
                }
            });

        $this->info("Expired {$expired} listing(s).");

        return self::SUCCESS;
    }
}
