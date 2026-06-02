<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('short_name')->nullable();
            $table->timestamps();
        });

        Schema::create('car_passage_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 10)->default('allow');
            $table->string('direction', 10)->default('both');
            $table->boolean('is_active')->default(true);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('car_passage_rule_car_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_passage_rule_id')->constrained('car_passage_rules')->onDelete('cascade');
            $table->foreignId('car_tag_id')->constrained('car_tags')->onDelete('cascade');
            $table->unique(['car_passage_rule_id', 'car_tag_id'], 'cpr_car_tag_unique');
            $table->timestamps();
        });

        Schema::create('car_passage_rule_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_passage_rule_id')->constrained('car_passage_rules')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('person')->onDelete('cascade');
            $table->unique(['car_passage_rule_id', 'person_id'], 'cpr_person_unique');
            $table->timestamps();
        });

        Schema::create('car_passage_rule_car', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_passage_rule_id')->constrained('car_passage_rules')->onDelete('cascade');
            $table->foreignId('car_id')->constrained('cars')->onDelete('cascade');
            $table->unique(['car_passage_rule_id', 'car_id'], 'cpr_car_unique');
            $table->timestamps();
        });

        Schema::create('car_passage_rule_passage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_passage_rule_id')->constrained('car_passage_rules')->onDelete('cascade');
            $table->unsignedBigInteger('passage_id'); // FK добавляется в 2026_06_02_160000
            $table->unique(['car_passage_rule_id', 'passage_id'], 'cpr_passage_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_passage_rule_passage');
        Schema::dropIfExists('car_passage_rule_car');
        Schema::dropIfExists('car_passage_rule_person');
        Schema::dropIfExists('car_passage_rule_car_tag');
        Schema::dropIfExists('car_passage_rules');
        Schema::dropIfExists('car_tags');
    }
};
