<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person', function (Blueprint $table) {
            $table->id();
            $table->string('last_name')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->string('grapesva_uuid')->nullable()->unique();
            $table->json('face_vector')->nullable();
            $table->string('vectorization_status')->default('pending');
            $table->text('vectorization_error')->nullable();
            $table->timestamp('vectorized_at')->nullable();
            $table->timestamps();

            $table->index('first_name');
            $table->index('last_name');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person');
    }
};
