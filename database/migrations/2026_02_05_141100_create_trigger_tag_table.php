<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trigger_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trigger_id')->constrained('triggers')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->unique(['trigger_id', 'tag_id']);

            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('trigger_tag');
    }
};
