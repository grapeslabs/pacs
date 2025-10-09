<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->comment('Внешний ID курьерского сервиса');
            $table->string('full_name')->comment('ФИО');
            $table->string('phone')->nullable()->comment('Мобильный телефон');
            $table->string('photo')->nullable()->comment('Фото');
            $table->string('document')->nullable()->comment('Документ');
            $table->text('comment')->nullable()->comment('Комментарий');
            $table->timestamps();
            
            // Индексы для поиска
            $table->index('external_id');
            $table->index('full_name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};