<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person', function (Blueprint $table) {
            $table->timestamp('frozen_start')->nullable();
            $table->timestamp('frozen_end')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('person', function (Blueprint $table) {
            $table->dropColumn('frozen_start');
            $table->dropColumn('frozen_end');
        });
    }
};
