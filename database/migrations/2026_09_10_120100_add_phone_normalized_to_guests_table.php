<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('guests', static function (Blueprint $table): void {
            $table->string('phone_normalized', 20)->nullable()->index();
        });

        $normalize = static function (?string $phone): string {
            $digits = preg_replace('/\D/', '', (string) $phone);

            if (strlen($digits) === 11 && $digits[0] === '8') {
                $digits = '7' . substr($digits, 1);
            }

            return $digits;
        };

        DB::table('guests')
            ->select('id', 'phone')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunk(500, static function ($rows) use ($normalize): void {
                foreach ($rows as $row) {
                    $digits = $normalize($row->phone);

                    DB::table('guests')
                        ->where('id', $row->id)
                        ->update(['phone_normalized' => $digits !== '' ? $digits : null]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('guests', static function (Blueprint $table): void {
            $table->dropColumn('phone_normalized');
        });
    }
};
