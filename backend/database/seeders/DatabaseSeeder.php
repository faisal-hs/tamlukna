<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data safe for every environment. Development sample listings
     * arrive in Step 7.2 (local environment only).
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LocationSeeder::class,
            CatalogSeeder::class,
        ]);
    }
}
