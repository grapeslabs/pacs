<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crossing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
            $table->string('camera_id')->nullable();
            $table->unsignedBigInteger('figure_event_id')->nullable();
            $table->string('type', 10);
            $table->string('zone_kind', 10);
            $table->unsignedInteger('zone_index')->default(0);
            $table->string('side', 5)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('camera_id');
            $table->index('type');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crossing_events');
    }
};
