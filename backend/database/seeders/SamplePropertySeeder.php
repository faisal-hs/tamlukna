<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\City;
use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * LOCAL DEVELOPMENT ONLY. Creates sample accounts (@tamlukna.test, password
 * "password") and clearly marked sample listings ("[تجريبي]" / "[Sample]").
 * Never real market data. Requires LocationSeeder and CatalogSeeder.
 * All-or-nothing: a failure leaves no partial sample data behind.
 */
final class SamplePropertySeeder extends Seeder
{
    private const DOMAIN = 'tamlukna.test';

    private const PASSWORD = 'password';

    /** @var array<string, Role> */
    private const ACCOUNTS = [
        'owner' => Role::Owner,
        'broker' => Role::Broker,
        'buyer' => Role::Buyer,
        'staff' => Role::Staff,
        'admin' => Role::Admin,
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->warn('SamplePropertySeeder skipped: it runs only in the local environment.');

            return;
        }

        $cities = City::query()->whereIn('slug', ['riyadh', 'jeddah'])->with('districts')->get();
        $types = PropertyType::query()->where('is_active', true)->get();
        $featureIds = Feature::query()->where('is_active', true)->pluck('id');

        if ($cities->isEmpty() || $types->isEmpty()) {
            $this->command->warn('Run LocationSeeder and CatalogSeeder first.');

            return;
        }

        $created = DB::transaction(function () use ($cities, $types, $featureIds): int {
            $accounts = [];
            foreach (self::ACCOUNTS as $name => $role) {
                $accounts[$name] = $this->account($name);
                $accounts[$name]->syncRoles([$role->value]);
            }

            $advertisers = [$accounts['owner'], $accounts['broker']];

            if (Property::query()->whereIn('advertiser_id', [$advertisers[0]->id, $advertisers[1]->id])->exists()) {
                return 0;
            }

            $statuses = array_merge(
                array_fill(0, 16, ListingStatus::Published),
                array_fill(0, 3, ListingStatus::Draft),
                array_fill(0, 3, ListingStatus::PendingReview),
                [ListingStatus::Rejected, ListingStatus::Paused],
            );

            foreach ($statuses as $i => $status) {
                $city = $cities[$i % $cities->count()];
                $district = $city->districts->isNotEmpty() ? $city->districts->random() : null;
                $type = $types->random();

                $factory = Property::factory()->forType($type)->state([
                    'advertiser_id' => $advertisers[$i % 3 === 0 ? 1 : 0]->id,
                    'city_id' => $city->id,
                    'district_id' => $district?->id,
                    'latitude' => round((float) ($city->latitude ?? 24.7136) + fake()->randomFloat(4, -0.08, 0.08), 7),
                    'longitude' => round((float) ($city->longitude ?? 46.6753) + fake()->randomFloat(4, -0.08, 0.08), 7),
                ]);

                if ($i % 3 === 1) {
                    $factory = $factory->forRent();
                }

                $property = $this->withStatus($factory, $status)->create();

                if ($type->allows_rooms && $featureIds->isNotEmpty()) {
                    $property->features()->attach($featureIds->random(min(3, $featureIds->count()))->all());
                }
            }

            return count($statuses);
        });

        if ($created === 0) {
            $this->command->info('Sample listings already exist; skipping.');

            return;
        }

        $this->command->info(sprintf(
            'Created %d sample listings. Sample accounts (local only, password "%s"): %s',
            $created,
            self::PASSWORD,
            implode(', ', array_map(fn (string $name): string => "{$name}@".self::DOMAIN, array_keys(self::ACCOUNTS))),
        ));
    }

    private function withStatus(PropertyFactory $factory, ListingStatus $status): PropertyFactory
    {
        return match ($status) {
            ListingStatus::Draft => $factory,
            ListingStatus::PendingReview => $factory->pendingReview(),
            ListingStatus::Published => $factory->published(),
            ListingStatus::Rejected => $factory->rejected(),
            ListingStatus::Paused => $factory->paused(),
            ListingStatus::Expired => $factory->expired(),
        };
    }

    private function account(string $name): User
    {
        $email = "{$name}@".self::DOMAIN;
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            return $user;
        }

        $user = new User;
        $user->forceFill([
            'name' => '[Sample] '.ucfirst($name),
            'email' => $email,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
