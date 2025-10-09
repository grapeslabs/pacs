<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Ключ настройки');
            $table->string('name')->comment('Название настройки');
            $table->text('description')->nullable()->comment('Описание настройки');
            $table->text('value')->nullable()->comment('Значение настройки');
            $table->string('type')->default('text')->comment('Тип поля: text, textarea, number, boolean, select, json');
            $table->json('options')->nullable()->comment('Опции для select типа');
            $table->string('group')->default('general')->comment('Группа настроек: general, system, integration, etc.');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('is_public')->default(false)->comment('Публичная настройка');
            $table->boolean('is_encrypted')->default(false)->comment('Зашифрованное значение');
            $table->timestamps();

            $table->index('key');
            $table->index('group');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};