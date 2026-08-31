<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_passage_events', function (Blueprint $table) {
            $table->index('car_id');
        });

        DB::statement("CREATE INDEX car_passage_events_plate_text_normalized_index ON car_passage_events (upper(replace(plate_text, ' ', '')))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS car_passage_events_plate_text_normalized_index');

        Schema::table('car_passage_events', function (Blueprint $table) {
            $table->dropIndex(['car_id']);
        });
    }
};
