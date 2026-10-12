<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_types', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('category', 20)->comment('App\Enums\PropertyCategory');
            $table->boolean('allows_rooms')->default(true)->comment('Bedrooms/bathrooms/living rooms apply');
            $table->boolean('allows_building_details')->default(true)->comment('Floor, total floors, building age apply');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('features', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('group', 20)->comment('App\Enums\FeatureGroup');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
        Schema::dropIfExists('property_types');
    }
};
