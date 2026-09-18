<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('presence_reports', static function (Blueprint $table): void {
            $table->string('params_hash', 32)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('presence_reports', static function (Blueprint $table): void {
            $table->dropColumn('params_hash');
        });
    }
};
