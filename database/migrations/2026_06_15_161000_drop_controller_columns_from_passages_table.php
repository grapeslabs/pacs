<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entry_controller_id');
            $table->dropConstrainedForeignId('exit_controller_id');
        });
    }

    public function down(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->foreignId('entry_controller_id')->nullable()
                ->constrained('grapeslabs_skud_controllers')->nullOnDelete();
            $table->foreignId('exit_controller_id')->nullable()
                ->constrained('grapeslabs_skud_controllers')->nullOnDelete();
        });
    }
};
