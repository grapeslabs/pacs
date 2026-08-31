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
            $table->string('source', 10)->default('manual');
            $table->index('source');
        });

        DB::statement('ALTER TABLE cars ALTER COLUMN brand_id DROP NOT NULL');
        DB::statement('ALTER TABLE cars ALTER COLUMN color_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cars ALTER COLUMN brand_id SET NOT NULL');
        DB::statement('ALTER TABLE cars ALTER COLUMN color_id SET NOT NULL');

        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
