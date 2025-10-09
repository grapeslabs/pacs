<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type')->default('Mifare');
            $table->foreignId('person_id')
                  ->nullable()
                  ->constrained('person')
                  ->nullOnDelete();
            $table->timestamps();
            
            $table->index('person_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('keys');
    }
};