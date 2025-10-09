<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person', function (Blueprint $table) {
            // Добавляем новое поле key_uid
            $table->string('key_uid')->nullable()->unique()->after('grapesva_uuid');

            $table->index('key_uid');
        });
    }

    public function down(): void
    {
        Schema::table('person', function (Blueprint $table) {
            $table->dropIndex(['key_uid']);
            $table->dropColumn('key_uid');
        });
    }
};
