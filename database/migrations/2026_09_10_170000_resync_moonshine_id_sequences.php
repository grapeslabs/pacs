<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    const TABLES = ['moonshine_user_roles', 'moonshine_users'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $sequence = DB::selectOne(
                'select pg_get_serial_sequence(?, ?) as name',
                [$table, 'id'],
            )?->name;

            if ($sequence === null) {
                continue;
            }

            DB::statement(
                "select setval(?, coalesce((select max(id) from {$table}), 1), (select max(id) is not null from {$table}))",
                [$sequence],
            );
        }
    }

    public function down(): void
    {
        //
    }
};
