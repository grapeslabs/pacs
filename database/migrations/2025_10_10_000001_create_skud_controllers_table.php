<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grapeslabs_skud_controllers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('type');
            $table->string('ip')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grapeslabs_skud_controllers');
    }
};