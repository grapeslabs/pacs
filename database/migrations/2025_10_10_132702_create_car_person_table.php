<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained('person')->onDelete('cascade'); // исправлено
            $table->timestamps();
            
            $table->unique(['car_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_person');
    }
};