<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn('storage_id');
        });
        Schema::table('streams', function (Blueprint $table) {
            $table->foreignId('storage_id')->nullable()->constrained('storages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropColumn('storage_id');
        });
        Schema::table('streams', function (Blueprint $table) {
            $table->string('storage_id')->nullable();
        });
    }
};
