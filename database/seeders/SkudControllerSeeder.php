<?php

namespace Database\Seeders;

use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Database\Seeder;

class SkudControllerSeeder extends Seeder
{
    const COUNTS = [
        'ironlogic' => 4,
        'z5rweb' => 2,
        'pinterm' => 14,
        'pingate' => 6,
    ];

    public function run(): void
    {
        $created = 0;

        foreach (self::COUNTS as $type => $count) {
            for ($index = 1; $index <= $count; $index++) {
                $created++;

                SkudController::create([
                    'serial_number' => sprintf('%s-%s-%04d', strtoupper($type), date('Ymd'), 1000 + $created),
                    'type' => $type,
                    'ip' => '10.0.' . intdiv($created, 254) . '.' . ($created % 254 + 1),
                ]);
            }
        }

        $this->command->info("✅ Создано контроллеров СКУД: {$created}");
    }
}
