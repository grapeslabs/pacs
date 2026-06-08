<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique('organizations_inn_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX organizations_inn_unique ON organizations (inn) WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS organizations_inn_unique');

        Schema::table('organizations', function (Blueprint $table) {
            $table->unique('inn', 'organizations_inn_unique');
        });
    }
};
