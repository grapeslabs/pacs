<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inviters');
    }

    public function down(): void
    {
        Schema::create('inviters', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->foreignId('person_id')
                ->constrained('person')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }
};
