<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_passage_events', function (Blueprint $table) {
            $table->index('car_id');
            $table->index('plate_text');
        });
    }

    public function down(): void
    {
        Schema::table('car_passage_events', function (Blueprint $table) {
            $table->dropIndex(['car_id']);
            $table->dropIndex(['plate_text']);
        });
    }
};
