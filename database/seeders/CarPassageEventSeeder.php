<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarPassageEvent;
use App\Models\Stream;
use Illuminate\Database\Seeder;

class CarPassageEventSeeder extends Seeder
{
    const EVENTS_COUNT = 300;

    public function run(): void
    {
        $streams = Stream::all();
        $cars    = Car::all();

        if ($streams->isEmpty()) {
            $this->command->warn('No streams found, camera_id will be empty.');
        }

        $this->command->info('Seeding car passage events...');
        $bar = $this->command->getOutput()->createProgressBar(self::EVENTS_COUNT);
        $bar->start();

        for ($i = 0; $i < self::EVENTS_COUNT; $i++) {
            $this->createEvent($streams, $cars);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->createSpecialEvents($streams, $cars);

        $total = CarPassageEvent::count();
        $this->command->info("Car passage events created: {$total}");
    }

    private function createEvent($streams, $cars): void
    {
        $stream = $streams->isNotEmpty() ? $streams->random() : null;
        $rand   = rand(1, 10);

        if ($rand <= 6 && $cars->isNotEmpty()) {
            $car = $cars->random();
            $this->makeEvent($stream, $car, $car->license_plate, CarPassageEvent::STATUS_IN_DB, true);
        } elseif ($rand <= 9) {
            $this->makeEvent($stream, null, $this->randomPlate(), CarPassageEvent::STATUS_NOT_IN_DB, false);
        } else {
            $this->makeEvent($stream, null, null, CarPassageEvent::STATUS_NOT_RECOGNIZED, false);
        }
    }

    private function createSpecialEvents($streams, $cars): void
    {
        $stream = $streams->first();
        $ivanovCar = $cars->first(fn($c) => str_contains($c->comment ?? '', 'Иванов'));
        if ($ivanovCar) {
            for ($i = 0; $i < 5; $i++) {
                $this->makeEvent($stream, $ivanovCar, $ivanovCar->license_plate, CarPassageEvent::STATUS_IN_DB, true, now()->subMinutes($i * 30));
            }
        }
        if ($stream) {
            $this->makeEvent($stream, null, 'А001АА 777', CarPassageEvent::STATUS_NOT_IN_DB, false, now()->subMinutes(5));
        }
    }

    private function makeEvent($stream, ?Car $car, ?string $plateText, string $status, bool $isAuthorized, $recognizedAt = null): void
    {
        CarPassageEvent::create([
            'recognized_plate_id' => null,
            'plate_text'          => $plateText !== '' ? $plateText : null,
            'camera_id'           => $stream?->uid,
            'stream_id'           => $stream?->id,
            'car_id'              => $car?->id,
            'car_passage_rule_id' => null,
            'rule_name'           => null,
            'status'              => $status,
            'is_authorized'       => $isAuthorized,
            'controllers'         => null,
            'image_path'          => null,
            'plate_image_path'    => null,
            'recognized_at'       => $recognizedAt ?? now()->subSeconds(rand(0, 30 * 24 * 3600)),
        ]);
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
