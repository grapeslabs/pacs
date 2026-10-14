<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('moonshine_users', static function (Blueprint $table): void {
            $table->boolean('is_super')->default(false);
        });

        Schema::table('moonshine_user_roles', static function (Blueprint $table): void {
            $table->boolean('is_super')->default(false);
        });

        DB::table('moonshine_users')->where('id', 1)->update(['is_super' => true]);
        DB::table('moonshine_user_roles')->where('id', 1)->update(['is_super' => true]);
    }

    public function down(): void
    {
        Schema::table('moonshine_users', static function (Blueprint $table): void {
            $table->dropColumn('is_super');
        });

        Schema::table('moonshine_user_roles', static function (Blueprint $table): void {
            $table->dropColumn('is_super');
        });
    }
};
