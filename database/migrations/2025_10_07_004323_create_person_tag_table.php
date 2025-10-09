<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('person_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('person')->onDelete('cascade'); // Явно указываем таблицу
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade'); // Явно указываем таблицу
            $table->timestamps();
            
            // Уникальная комбинация person_id + tag_id
            $table->unique(['person_id', 'tag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('person_tag');
    }
};