<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_passage_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 10)->default('allow');
            $table->boolean('is_active')->default(true);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('person_passage_rule_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_passage_rule_id')->constrained('person_passage_rules')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('person')->onDelete('cascade');
            $table->unique(['person_passage_rule_id', 'person_id'], 'ppr_person_unique');
            $table->timestamps();
        });

        Schema::create('person_passage_rule_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_passage_rule_id')->constrained('person_passage_rules')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->unique(['person_passage_rule_id', 'tag_id'], 'ppr_tag_unique');
            $table->timestamps();
        });

        Schema::create('person_passage_rule_key', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_passage_rule_id')->constrained('person_passage_rules')->onDelete('cascade');
            $table->foreignId('key_id')->constrained('keys')->onDelete('cascade');
            $table->unique(['person_passage_rule_id', 'key_id'], 'ppr_key_unique');
            $table->timestamps();
        });

        Schema::create('person_passage_rule_controller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_passage_rule_id')->constrained('person_passage_rules')->onDelete('cascade');
            $table->foreignId('controller_id')->constrained('grapeslabs_skud_controllers')->onDelete('cascade');
            $table->unique(['person_passage_rule_id', 'controller_id'], 'ppr_controller_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_passage_rule_controller');
        Schema::dropIfExists('person_passage_rule_key');
        Schema::dropIfExists('person_passage_rule_tag');
        Schema::dropIfExists('person_passage_rule_person');
        Schema::dropIfExists('person_passage_rules');
    }
};
