<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Property domain tables. See docs/decisions/0003-property-schema.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->char('reference', 12)->unique()->comment('Public reference, TML-XXXXXXXX');
            $table->foreignId('advertiser_id')
                ->comment('Account responsible for the listing (owner, broker or developer)')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('property_type_id')->constrained()->restrictOnDelete();

            $table->string('purpose', 10)->comment('App\Enums\PropertyPurpose');
            $table->string('rent_period', 10)->nullable()->comment('Rent listings only');
            $table->string('property_condition', 10)->nullable()->comment('new or used; not applicable to land');
            $table->string('status', 20)->default('draft')->comment('App\Enums\ListingStatus');
            $table->string('verification_status', 20)->default('unverified')->comment('App\Enums\VerificationStatus');

            $table->string('title_ar', 150);
            $table->string('title_en', 150)->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('address_line', 255)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            // "location" (generated POINT + SPATIAL INDEX) is added below with raw SQL.

            $table->decimal('area_sqm', 12, 2);
            $table->decimal('street_width_m', 6, 2)->nullable();
            $table->string('facade', 15)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('living_rooms')->nullable();
            $table->tinyInteger('floor_number')->nullable()->comment('0 = ground floor; negative = basement');
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->unsignedTinyInteger('building_age_years')->nullable();
            $table->string('furnishing', 20)->nullable();

            $table->decimal('price', 14, 2);
            $table->char('currency', 3)->default('SAR');
            $table->decimal('price_per_sqm', 14, 2)->nullable()->storedAs('ROUND(price / NULLIF(area_sqm, 0), 2)');

            $table->string('ad_license_number', 50)->nullable()
                ->comment('Advertising licence number as provided by the advertiser; not verified by Tamlukna');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'purpose', 'city_id', 'property_type_id'], 'properties_search_index');
            $table->index(['status', 'published_at']);
            $table->index(['status', 'price']);
            $table->index(['status', 'price_per_sqm']);
            $table->index(['status', 'area_sqm']);
            $table->index(['status', 'expires_at']);
            $table->index(['advertiser_id', 'status']);
        });

        // Laravel's schema builder emits invalid SQL for a generated column with an SRID,
        // so this column is added directly. POINT(x, y) = POINT(longitude, latitude);
        // MySQL validates the ranges and reports latitude first for SRID 4326.
        DB::statement(<<<'SQL'
            ALTER TABLE properties
                ADD COLUMN location POINT
                    GENERATED ALWAYS AS (ST_SRID(POINT(longitude, latitude), 4326)) STORED
                    SRID 4326 NOT NULL AFTER longitude,
                ADD SPATIAL INDEX properties_location_spatial (location)
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE properties
                ADD CONSTRAINT properties_price_non_negative CHECK (price >= 0),
                ADD CONSTRAINT properties_area_positive CHECK (area_sqm > 0),
                ADD CONSTRAINT properties_street_width_non_negative CHECK (street_width_m IS NULL OR street_width_m >= 0)
            SQL);

        Schema::create('property_feature', function (Blueprint $table): void {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->restrictOnDelete();

            $table->primary(['property_id', 'feature_id']);
            $table->index('feature_id');
        });

        Schema::create('property_media', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            // RESTRICT (not CASCADE): MySQL forbids cascading on the base column of the
            // generated cover column, and stored files must be deleted by the application first.
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('disk', 30);
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->unsignedBigInteger('cover_property_id')->nullable()
                ->storedAs('IF(is_cover = 1, property_id, NULL)')
                ->comment('Unique: at most one cover image per property');
            $table->timestamps();

            $table->unique('cover_property_id');
            $table->unique(['disk', 'path']);
            $table->index(['property_id', 'sort_order']);
        });

        Schema::create('property_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 1000)->nullable()->comment('e.g. rejection reason shown to the advertiser');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['property_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_status_histories');
        Schema::dropIfExists('property_media');
        Schema::dropIfExists('property_feature');
        Schema::dropIfExists('properties');
    }
};
