<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название бота');
            $table->enum('service', ['telegram', 'whatsapp', 'viber', 'sms'])->default('telegram')->comment('Сервис');
            $table->string('token')->comment('Ключ-токен доступа');
            $table->string('api_url')->nullable()->comment('API адрес сервиса');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
