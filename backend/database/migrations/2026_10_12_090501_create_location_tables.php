<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique()->comment('ISO 3166-2:SA code, e.g. SA-01');
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('data_source', 50)->nullable()->comment('Origin of the row, e.g. "sample" or a licensed dataset');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->decimal('latitude', 10, 7)->nullable()->comment('Approximate centre, for map defaults only');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('data_source', 50)->nullable();
            $table->timestamps();

            $table->index(['region_id', 'is_active']);
        });

        Schema::create('districts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('slug', 100);
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('data_source', 50)->nullable();
            $table->timestamps();

            $table->unique(['city_id', 'slug']);
            $table->index(['city_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('regions');
    }
};
