<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->foreignId('entry_actuator_device_id')->nullable()
                ->after('entry_controller_id')
                ->constrained('actuator_devices')->nullOnDelete();
            $table->foreignId('exit_actuator_device_id')->nullable()
                ->after('exit_controller_id')
                ->constrained('actuator_devices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entry_actuator_device_id');
            $table->dropConstrainedForeignId('exit_actuator_device_id');
        });
    }
};
