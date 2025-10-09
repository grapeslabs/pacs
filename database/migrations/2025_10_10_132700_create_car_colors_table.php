<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_colors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Название цвета');
            $table->string('hex_code')->nullable()->comment('HEX код цвета');
            $table->boolean('is_active')->default(true)->comment('Активен');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_colors');
    }
};