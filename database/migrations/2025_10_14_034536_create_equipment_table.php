<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('type');
            $table->string('skud_controller_sn')->nullable();
            $table->string('person_uid')->nullable();
            $table->string('person_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
