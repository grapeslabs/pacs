<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            foreach ([
                'kpp'              => fn() => $table->string('kpp')->nullable(),
                'ogrn'             => fn() => $table->string('ogrn')->nullable(),
                'actual_address'   => fn() => $table->text('actual_address')->nullable(),
                'contact_position' => fn() => $table->string('contact_position')->nullable(),
                'contact_name'     => fn() => $table->string('contact_name')->nullable(),
                'phone'            => fn() => $table->string('phone')->nullable(),
            ] as $column => $make) {
                if (! Schema::hasColumn('organizations', $column)) {
                    $make();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            foreach (['kpp', 'ogrn', 'actual_address', 'contact_position', 'contact_name', 'phone'] as $column) {
                if (Schema::hasColumn('organizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
