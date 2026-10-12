<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner decision (ADR 0003): every listing has a district, and that district
 * must belong to the listing's city — enforced by a composite foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('properties')->whereNull('district_id')->exists()) {
            throw new RuntimeException('Some properties have no district. Assign districts (or remove those rows) before running this migration.');
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropForeign(['district_id']);
        });

        Schema::table('districts', function (Blueprint $table): void {
            $table->unique(['id', 'city_id'], 'districts_id_city_id_unique');
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedBigInteger('district_id')->nullable(false)->change();
            $table->foreign(['district_id', 'city_id'], 'properties_district_city_foreign')
                ->references(['id', 'city_id'])->on('districts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropForeign('properties_district_city_foreign');
            $table->unsignedBigInteger('district_id')->nullable()->change();
            $table->foreign('district_id')->references('id')->on('districts')->restrictOnDelete();
        });

        Schema::table('districts', function (Blueprint $table): void {
            $table->dropUnique('districts_id_city_id_unique');
        });
    }
};
