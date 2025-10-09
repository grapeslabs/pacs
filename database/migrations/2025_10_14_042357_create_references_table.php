<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название справочника');
            $table->string('code')->unique()->comment('Код справочника');
            $table->text('description')->nullable()->comment('Описание');
            $table->string('type')->comment('Тип справочника: status, category, type, etc.');
            $table->json('data')->nullable()->comment('Данные справочника в формате JSON');
            $table->boolean('is_active')->default(true)->comment('Активен');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->timestamps();

            $table->index('type');
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('references');
    }
};