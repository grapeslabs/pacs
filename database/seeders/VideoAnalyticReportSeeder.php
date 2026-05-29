<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VideoAnalyticReportSeeder extends Seeder
{
    const EVENTS_COUNT = 200;

    public function run(): void
    {
        try {
            DB::connection('analytic-database')->getPdo();
        } catch (\Exception $e) {
            $this->command->warn('⚠️ Соединение analytic-database недоступно, пропускаем VideoAnalyticReportSeeder.');
            return;
        }

        $streams = Stream::all();
        $persons = Person::whereNotNull('grapesva_uuid')->get();

        if ($streams->isEmpty()) {
            $this->command->warn('⚠️ Нет видеопотоков, camera_id будет пустым.');
        }

        $this->command->info('📊 Создание событий аналитики лиц...');
        $bar = $this->command->getOutput()->createProgressBar(self::EVENTS_COUNT);
        $bar->start();

        for ($i = 0; $i < self::EVENTS_COUNT; $i++) {
            $this->createEvent($streams, $persons);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $total = VideoAnalyticReport::count();
        $this->command->info("✅ Создано событий аналитики: {$total}");
    }

    private function createEvent($streams, $persons): void
    {
        $stream = $streams->isNotEmpty() ? $streams->random() : null;
        $isKnown = $persons->isNotEmpty() && rand(1, 10) <= 7;

        $datetime  = now()->subSeconds(rand(0, 30 * 24 * 3600));
        $cameraId  = $stream?->uid ?? (string) Str::uuid();

        if ($isKnown) {
            $person = $persons->random();
            VideoAnalyticReport::create([
                'datetime'            => $datetime,
                'camera_id'           => $cameraId,
                'type'                => 'face_recognition',
                'person_photobank_id' => $person->grapesva_uuid,
                'event_id'            => (string) Str::uuid(),
                'is_unknown'          => false,
                'data'                => ['snapshot_path' => 'thumbnails/' . Str::uuid() . '.jpg'],
                'created_at'          => $datetime,
            ]);
        } else {
            $unknownUuid = (string) Str::uuid();
            VideoAnalyticReport::create([
                'datetime'            => $datetime,
                'camera_id'           => $cameraId,
                'type'                => 'face_recognition',
                'person_photobank_id' => $unknownUuid,
                'event_id'            => (string) Str::uuid(),
                'is_unknown'          => true,
                'data'                => [
                    'snapshot_path' => 'thumbnails/' . Str::uuid() . '.jpg',
                    'unknown_uuid'  => $unknownUuid,
                ],
                'created_at'          => $datetime,
            ]);
        }
    }
}