<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('entry_controller_id')
                ->nullable()
                ->constrained('grapeslabs_skud_controllers')
                ->nullOnDelete();
            $table->foreignId('exit_controller_id')
                ->nullable()
                ->constrained('grapeslabs_skud_controllers')
                ->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('passage_entry_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passage_id')->constrained('passages')->onDelete('cascade');
            $table->foreignId('stream_id')->constrained('streams')->onDelete('cascade');
            $table->unique(['passage_id', 'stream_id'], 'pec_unique');
            $table->timestamps();
        });

        Schema::create('passage_exit_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passage_id')->constrained('passages')->onDelete('cascade');
            $table->foreignId('stream_id')->constrained('streams')->onDelete('cascade');
            $table->unique(['passage_id', 'stream_id'], 'pxc_unique');
            $table->timestamps();
        });

        Schema::table('car_passage_rule_passage', function (Blueprint $table) {
            $table->foreign('passage_id')->references('id')->on('passages')->onDelete('cascade');
        });

        Schema::table('car_passage_events', function (Blueprint $table) {
            $table->foreign('passage_id')->references('id')->on('passages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('car_passage_events', function (Blueprint $table) {
            $table->dropForeign(['passage_id']);
        });
        Schema::table('car_passage_rule_passage', function (Blueprint $table) {
            $table->dropForeign(['passage_id']);
        });
        Schema::dropIfExists('passage_exit_cameras');
        Schema::dropIfExists('passage_entry_cameras');
        Schema::dropIfExists('passages');
    }
};
