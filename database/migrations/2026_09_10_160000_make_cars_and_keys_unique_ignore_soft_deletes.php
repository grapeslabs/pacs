<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropUnique('cars_license_plate_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX cars_license_plate_unique ON cars (license_plate) WHERE deleted_at IS NULL'
        );

        Schema::table('keys', function (Blueprint $table) {
            $table->dropUnique('keys_key_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX keys_key_unique ON keys (key) WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS keys_key_unique');

        Schema::table('keys', function (Blueprint $table) {
            $table->unique('key', 'keys_key_unique');
        });

        DB::statement('DROP INDEX IF EXISTS cars_license_plate_unique');

        Schema::table('cars', function (Blueprint $table) {
            $table->unique('license_plate', 'cars_license_plate_unique');
        });
    }
};
