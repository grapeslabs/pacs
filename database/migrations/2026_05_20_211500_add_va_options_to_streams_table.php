<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->json('va_options')->nullable();
            $table->dropColumn('is_recognize');
        });
    }

    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn('va_options');
            $table->boolean('is_recognize')->default(false);
        });
    }
};
