<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data is safe for every environment. Sample accounts and listings
     * are created only in the local environment.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LocationSeeder::class,
            CatalogSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(SamplePropertySeeder::class);
        }
    }
}
