<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Development sample data arrives with
     * the property schema (Step 7).
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
