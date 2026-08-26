<?php

namespace App\Console\Commands;
use App\Jobs\SendNotification;
use App\Models\Person;
use App\Models\Stream;
use App\Models\Trigger;
use App\Models\VideoAnalyticReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessEvents extends Command
{
    protected $signature = 'events:process-new';
    protected int $lockTimeout = 120;

    protected int $heartbeatEvery = 300;

    public function handle(): int
    {
        $this->info('Служба обработки событий распознавания лиц запущена...');
        Log::info('Служба обработки событий запущена');

        $iterations = 0;
        $maxIterations = 1000;
        while ($iterations < $maxIterations) {
            try {
                $this->process($iterations);
            } catch (\Throwable $e) {
                Log::error('Критическая ошибка: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
            $iterations++;
            sleep(1);
        }

        $this->info('Плановый перезапуск воркера...');
        Log::info('Плановый перезапуск службы обработки событий', ['iterations' => $iterations]);

        return 0;
    }

    protected function process(int $iteration = 0): void
    {
        $triggers = Trigger::where('is_active', true)->get();

        if ($iteration % $this->heartbeatEvery === 0) {
            Log::info('Служба обработки событий активна', [
                'active_triggers' => $triggers->count(),
                'iteration' => $iteration,
            ]);
        }

        foreach ($triggers as $trigger) {
            $this->info("Обработка триггера ID {$trigger->id}");
            match ($trigger->device_type) {
                Trigger::DEVICE_CAMERA =>$this->processStream($trigger),
                default => null,
            };
        }
    }

    private function processStream($trigger)
    {
        $stream = Stream::find($trigger->device_id);
        if (!$stream) return;

        $bot = $trigger->bot;
        $data = $trigger->data;

        $cacheKey = "last_processed_{$trigger->id}";
        $lastId = Cache::get($cacheKey, 0);
        if($trigger->event_type == Trigger::EVENT_KNOWED) {
            $personIds = collect($trigger->tags)->flatMap(function ($tag) {
                return $tag->persons->pluck('id');
            })->unique()->filter()->toArray();
        }
        $events = VideoAnalyticReport::query()
            ->where('datetime', '>', $trigger->updated_at)
            ->where('camera_id', '=', $stream->uid)
            ->where('id', '>', $lastId)
            ->where('is_unknown', '=',$trigger->event_type == Trigger::EVENT_UNKNOWED)
            ->when(
                ($trigger->event_type == Trigger::EVENT_KNOWED) && !empty($personIds),
                fn($query) => $query->whereIn('person_photobank_id', $personIds))
            ->get();

        $events_count = $events->count();
        $this->info("Найдено событий: {$events_count}");

        if ($events_count > 0) {
            Log::info('Найдены новые события распознавания', [
                'trigger_id' => $trigger->id,
                'stream' => $stream->name,
                'event_type' => $trigger->event_type,
                'count' => $events_count,
            ]);
        }
        $maxProcessedId = $lastId;
        foreach ($events->toArray() as $event) {
            $photos = [];
            try {
                echo "Обработка события ID {$event['id']}\n";
                $timestamp = Carbon::parse($event['datetime']);
                if (!empty($event['person_photobank_id'])) {
                    $person = Person::find($event['person_photobank_id']);
                    $this->info("Персона: " . $person->getFullName() . "({$person->id}) | {$person->tags_list}");
                }

                Log::info('Обработка события распознавания', [
                    'event_id' => $event['id'],
                    'trigger_id' => $trigger->id,
                    'stream' => $stream->name,
                    'person' => isset($person) ? $person->getFullName() : null,
                    'is_unknown' => (bool) $event['is_unknown'],
                    'datetime' => $timestamp->format('d.m.Y H:i:s'),
                ]);
                $message = "Обнаружение!";
                if (!empty($data['date'])) $message .= "\nДата: " . $timestamp->format('d.m.Y');
                if (!empty($data['time'])) $message .= "\nВремя: " . $timestamp->format('H:i:s');
                if (!empty($data['stream'])) $message .= "\nВидеопоток: {$stream->name} | {$stream->location}";
                if ($trigger->event_type == Trigger::EVENT_KNOWED) {
                    if (!empty($person)) {
                        if (!empty($data['lfm'])) $message .= "\nФИО: " . $person->getFullName();
                        if (!empty($data['person_id'])) $message .= "\nID персоны: {$person->id}";
                        if (!empty($data['comment'])) $message .= "\nКомментарий: {$person->comment}";
                        if (!empty($data['photobank'])) $photos[] = Storage::disk('public')->path("person/photos/" . basename($person->photo[0]));
                    } else {
                        $message .= "\nВнимание: Данные персоны не найдены в базе";
                    }
                }
                if(!empty($data['photo'])) {
                    $photos[] = Storage::disk('analytic')->path("thumbnails/" . basename($event['data']['snapshot_path']));
                }
                try {
                   SendNotification::dispatch($bot->id, $message, $photos??null);

                   Log::info('Уведомление поставлено в очередь', [
                       'event_id' => $event['id'],
                       'bot_id' => $bot->id,
                       'photos' => count($photos),
                   ]);
                } catch (\Throwable $e) {
                    $this->error("Ошибка отправки в очередь: " . $e->getMessage());
                    Log::error('Не удалось поставить уведомление в очередь', [
                        'event_id' => $event['id'],
                        'bot_id' => $bot->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
               }
                $maxProcessedId = $event['id'];
            } catch (\Throwable $e) {
                $this->error("Ошибка обработки события ID {$event['id']}: " . $e->getMessage());
                Log::error('Ошибка обработки события распознавания', [
                    'event_id' => $event['id'],
                    'trigger_id' => $trigger->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        if ($maxProcessedId > $lastId) {
            Cache::put($cacheKey, $maxProcessedId);
        }
    }
}
