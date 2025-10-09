<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skud_event_car_plates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('grapeslabs_skud_events')->onDelete('cascade');
            $table->string('car_plate')->nullable()->index();
            $table->timestamps();

            $table->index(['car_plate', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skud_event_car_plates');
    }
};
