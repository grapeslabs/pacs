<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('person', function (Blueprint $table) {
            if (! Schema::hasColumn('person', 'frozen_reason')) {
                $table->string('frozen_reason')->nullable()->after('frozen_end');
            }
        });
    }

    public function down(): void
    {
        Schema::table('person', function (Blueprint $table) {
            if (Schema::hasColumn('person', 'frozen_reason')) {
                $table->dropColumn('frozen_reason');
            }
        });
    }
};
