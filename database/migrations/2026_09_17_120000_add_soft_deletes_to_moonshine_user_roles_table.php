<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('moonshine_user_roles', 'deleted_at')) {
            return;
        }

        Schema::table('moonshine_user_roles', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('moonshine_user_roles', 'deleted_at')) {
            return;
        }

        Schema::table('moonshine_user_roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
