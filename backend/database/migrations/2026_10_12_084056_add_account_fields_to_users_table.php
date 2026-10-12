<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('status', 20)->default('active')->after('password');
            $table->string('preferred_locale', 5)->default('ar')->after('status');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');

            $table->unique('phone');
            $table->index('status');
        });

        // Backfill public IDs for any existing rows before making the column required.
        DB::table('users')->whereNull('public_id')->lazyById()->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['public_id' => strtolower((string) Str::ulid())]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);
            $table->dropColumn(['public_id', 'phone', 'phone_verified_at', 'status', 'preferred_locale', 'last_login_at']);
        });
    }
};
