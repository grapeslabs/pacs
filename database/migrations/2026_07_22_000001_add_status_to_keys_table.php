<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('type');
            $table->date('issued_at')->nullable()->after('is_active');
            $table->date('expires_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'issued_at', 'expires_at']);
        });
    }
};
