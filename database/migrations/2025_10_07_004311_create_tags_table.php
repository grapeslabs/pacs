<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Полное название тега (например, "руководство")
            $table->string('short_name')->nullable(); // Сокращенное название (например, "рук")
            $table->timestamps();
            
            $table->unique('name'); // Уникальность названия тега
        });
    }

    public function down()
    {
        Schema::dropIfExists('tags');
    }
};