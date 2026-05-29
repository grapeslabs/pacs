<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\GrzReport;
use App\Models\Stream;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrzReportSeeder extends Seeder
{
    const EVENTS_COUNT = 300;

    public function run(): void
    {
        try {
            DB::connection('grz-database')->getPdo();
        } catch (\Exception $e) {
            $this->command->warn('⚠️ Соединение grz-database недоступно, пропускаем GrzReportSeeder.');
            return;
        }

        $streams = Stream::all();
        $cars    = Car::all();

        if ($streams->isEmpty()) {
            $this->command->warn('⚠️ Нет видеопотоков, camera_id будет пустым.');
        }

        $this->command->info('📊 Создание событий распознавания номеров...');
        $bar = $this->command->getOutput()->createProgressBar(self::EVENTS_COUNT);
        $bar->start();

        for ($i = 0; $i < self::EVENTS_COUNT; $i++) {
            $this->createEvent($streams, $cars);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->createSpecialEvents($streams, $cars);

        $total = GrzReport::count();
        $this->command->info("✅ Создано событий распознавания: {$total}");
    }

    private function createEvent($streams, $cars): void
    {
        $stream = $streams->isNotEmpty() ? $streams->random() : null;
        $rand   = rand(1, 10);

        if ($rand <= 6 && $cars->isNotEmpty()) {
            $car = $cars->random();
            GrzReport::create([
                'plate_text'   => $car->license_plate,
                'camera_id'    => $stream?->uid,
                'user_id'      => '1',
                'is_authorized' => true,
                'image'        => null,
                'plate'        => null,
                'created_at'   => now()->subSeconds(rand(0, 30 * 24 * 3600)),
            ]);
        } elseif ($rand <= 9) {
            GrzReport::create([
                'plate_text'   => $this->randomPlate(),
                'camera_id'    => $stream?->uid,
                'user_id'      => '1',
                'is_authorized' => false,
                'image'        => null,
                'plate'        => null,
                'created_at'   => now()->subSeconds(rand(0, 30 * 24 * 3600)),
            ]);
        } else {
            GrzReport::create([
                'plate_text'   => '',
                'camera_id'    => $stream?->uid,
                'user_id'      => '1',
                'is_authorized' => false,
                'image'        => null,
                'plate'        => null,
                'created_at'   => now()->subSeconds(rand(0, 30 * 24 * 3600)),
            ]);
        }
    }

    private function createSpecialEvents($streams, $cars): void
    {
        $stream = $streams->first();
        $ivanovCar = $cars->first(fn($c) => str_contains($c->comment ?? '', 'Иванов'));
        if ($ivanovCar) {
            for ($i = 0; $i < 5; $i++) {
                GrzReport::create([
                    'plate_text'   => $ivanovCar->license_plate,
                    'camera_id'    => $stream?->uid,
                    'user_id'      => '1',
                    'is_authorized' => true,
                    'image'        => null,
                    'plate'        => null,
                    'created_at'   => now()->subMinutes($i * 30),
                ]);
            }
        }
        if ($stream) {
            GrzReport::create([
                'plate_text'   => 'А001АА 777',
                'camera_id'    => $stream->uid,
                'user_id'      => '1',
                'is_authorized' => true,
                'image'        => null,
                'plate'        => null,
                'created_at'   => now()->subMinutes(5),
            ]);
        }
    }

    private function randomPlate(): string
    {
        $letters = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];
        $l1 = $letters[array_rand($letters)];
        $l2 = $letters[array_rand($letters)];
        $l3 = $letters[array_rand($letters)];
        $nums = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $regions = ['77', '99', '78', '50', '23', '16', '74', '63', '66', '54'];
        $region = $regions[array_rand($regions)];

        return $l1 . $nums . $l2 . $l3 . ' ' . $region;
    }
}