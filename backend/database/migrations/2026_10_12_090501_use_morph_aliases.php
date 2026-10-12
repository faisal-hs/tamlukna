<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Polymorphic columns now store stable aliases ("user") instead of PHP class
 * names (see AppServiceProvider::configureMorphMap). Converts existing rows.
 */
return new class extends Migration
{
    private const CLASS_NAME = 'App\\Models\\User';

    private const ALIAS = 'user';

    public function up(): void
    {
        foreach ($this->columns() as $table => $column) {
            DB::table($table)->where($column, self::CLASS_NAME)->update([$column => self::ALIAS]);
        }
    }

    public function down(): void
    {
        foreach ($this->columns() as $table => $column) {
            DB::table($table)->where($column, self::ALIAS)->update([$column => self::CLASS_NAME]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function columns(): array
    {
        return [
            'personal_access_tokens' => 'tokenable_type',
            (string) config('permission.table_names.model_has_roles', 'model_has_roles') => 'model_type',
            (string) config('permission.table_names.model_has_permissions', 'model_has_permissions') => 'model_type',
        ];
    }
};
