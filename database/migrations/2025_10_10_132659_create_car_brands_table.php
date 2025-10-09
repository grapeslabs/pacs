<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Название марки');
            $table->string('code')->unique()->nullable()->comment('Код марки');
            $table->boolean('is_active')->default(true)->comment('Активна');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_brands');
    }
};