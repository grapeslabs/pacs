<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grapeslabs_skud_events', function (Blueprint $table) {
            $table->id();
            $table->datetime('datetime');
            $table->string('event_id')->nullable();
            $table->foreignId('controller_id')->constrained('grapeslabs_skud_controllers');
            $table->string('type');
            $table->json('event');
            $table->timestamps();

            $table->index(['controller_id', 'datetime']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('grapeslabs_skud_events');
    }
};