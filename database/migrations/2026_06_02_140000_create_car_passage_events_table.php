<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_passage_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recognized_plate_id')->nullable()->unique();
            $table->string('plate_text')->nullable();
            $table->string('camera_id')->nullable();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
            $table->foreignId('car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->foreignId('car_passage_rule_id')->nullable()->constrained('car_passage_rules')->nullOnDelete();
            $table->string('rule_name')->nullable();
            $table->unsignedBigInteger('passage_id')->nullable();
            $table->string('direction', 10)->nullable();
            $table->string('status')->default('not_recognized');
            $table->boolean('is_authorized')->nullable();
            $table->json('controllers')->nullable();
            $table->string('image_path')->nullable();
            $table->string('plate_image_path')->nullable();
            $table->timestamp('recognized_at')->nullable();
            $table->timestamps();

            $table->index('camera_id');
            $table->index('status');
            $table->index('passage_id');
            $table->index('recognized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_passage_events');
    }
};
