<?php

namespace App\Console\Commands;

use App\Jobs\SendNotification;
use App\Models\AnalyticsFigureEvent;
use App\Models\CrossingEvent;
use App\Models\Stream;
use App\Models\Trigger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessZoneCrossings extends Command
{
    protected $signature = 'events:process-crossings';

    protected int $heartbeatEvery = 300;

    private const CURSOR_KEY = 'zone_crossings_last_figure_event_id';
    private const LOCK_KEY = 'zone_crossings_worker_instance';
    private const LOCK_TTL = 120;
    private const BATCH_SIZE = 200;

    private string $instanceToken = '';
    private array $stats = [];
    private array $lastRowTs = [];

    public function handle(): int
    {
        if (! config('services.va.enabled')) {
            sleep(60);

            return 0;
        }

        $this->instanceToken = uniqid('crossings-', true);
        if (! Cache::add(self::LOCK_KEY, $this->instanceToken, self::LOCK_TTL)) {
            $this->error('Служба обработки пересечений уже запущена в другом процессе');
            Log::error('Обработка пересечений: обнаружен второй экземпляр службы, выход');
            sleep(30);

            return 0;
        }

        $this->info('Служба обработки пересечений запущена...');
        Log::info('Служба обработки пересечений запущена', ['instance' => $this->instanceToken]);

        $iterations = 0;
        $maxIterations = 1000;
        while ($iterations < $maxIterations) {
            if (Cache::get(self::LOCK_KEY) !== $this->instanceToken) {
                Log::error('Обработка пересечений: экземпляр вытеснен другим процессом, остановка');
                break;
            }
            Cache::put(self::LOCK_KEY, $this->instanceToken, self::LOCK_TTL);
            try {
                $this->process($iterations);
            } catch (Throwable $e) {
                Log::error('Критическая ошибка обработки пересечений: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
            $iterations++;
            sleep(1);
        }

        $this->info('Плановый перезапуск воркера...');
        Log::info('Плановый перезапуск службы обработки пересечений', ['iterations' => $iterations]);

        return 0;
    }

    protected function process(int $iteration = 0): void
    {
        if ($iteration % $this->heartbeatEvery === 0) {
            $this->flushStats();
            Log::info('Служба обработки пересечений активна', ['iteration' => $iteration]);
        }

        $lastId = Cache::get(self::CURSOR_KEY);

        if ($lastId === null) {
            $lastId = (int) AnalyticsFigureEvent::query()->max('id');
            Cache::forever(self::CURSOR_KEY, $lastId);
            Log::warning('Курсор обработки пересечений потерян, установлен на текущий максимум', ['id' => $lastId]);

            return;
        }

        $rows = $this->fetchRows((int) $lastId);

        if ($rows === []) {
            return;
        }

        $this->handleRows($rows);

        Cache::put(self::CURSOR_KEY, (int) $rows[count($rows) - 1]['id']);
    }

    private function fetchRows(int $lastId): array
    {
        return DB::connection('analytic-database')
            ->table('analytics_figure_events')
            ->select('id', 'event_id', 'datetime', 'camera_id', 'snapshot_path')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit(self::BATCH_SIZE)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'event_id' => (string) $row->event_id,
                'datetime' => (string) $row->datetime,
                'camera_id' => (string) $row->camera_id,
                'snapshot_path' => (string) ($row->snapshot_path ?? ''),
            ])
            ->all();
    }

    private function handleRows(array $rows): void
    {
        $streams = Stream::query()
            ->whereIn('uid', array_unique(array_column($rows, 'camera_id')))
            ->get()
            ->keyBy('uid');

        $triggers = $this->crossingTriggers();

        foreach ($rows as $row) {
            try {
                $this->handleRow($row, $streams, $triggers);
            } catch (Throwable $e) {
                $this->error("Ошибка обработки строки ID {$row['id']}: " . $e->getMessage());
                Log::error('Ошибка обработки пересечения', [
                    'row_id' => $row['id'],
                    'camera' => $row['camera_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function handleRow(array $row, $streams, array $triggers): void
    {
        $uid = $row['camera_id'];
        $stream = $streams->get($uid);

        if (!$stream) {
            return;
        }

        $this->trackRowStats($row, $uid);

        $photoPath = $row['snapshot_path'] !== '' ? 'crossings/' . ltrim($row['snapshot_path'], '/') : null;

        CrossingEvent::query()->create([
            'stream_id' => $stream->id,
            'camera_id' => $uid,
            'figure_event_id' => $row['id'],
            'type' => CrossingEvent::TYPE_CROSS,
            'zone_kind' => 'line',
            'zone_index' => 0,
            'side' => null,
            'photo_path' => $photoPath,
            'occurred_at' => Carbon::parse($row['datetime']),
        ]);

        $this->stats[$uid]['events'] = ($this->stats[$uid]['events'] ?? 0) + 1;

        foreach ($triggers[$uid] ?? [] as $trigger) {
            $this->notify($trigger, $row, $stream, $photoPath);
        }
    }

    private function crossingTriggers(): array
    {
        return Trigger::query()
            ->where('is_active', true)
            ->where('device_type', Trigger::DEVICE_CAMERA)
            ->where('event_type', Trigger::EVENT_CROSSING)
            ->with(['stream', 'bot.chats'])
            ->get()
            ->filter(fn($trigger) => $trigger->stream && $trigger->bot && $trigger->bot->chats->isNotEmpty())
            ->groupBy(fn($trigger) => $trigger->stream->uid)
            ->all();
    }

    private function notify(Trigger $trigger, array $row, Stream $stream, ?string $photoPath): void
    {
        $data = is_array($trigger->data) ? $trigger->data : [];
        $message = self::buildMessage($stream, $data, $row['datetime']);

        $photos = [];
        if (!empty($data['photo']) && $photoPath && Storage::disk('analytic')->exists($photoPath)) {
            $photos[] = Storage::disk('analytic')->path($photoPath);
        }

        foreach ($trigger->bot->chats as $chat) {
            SendNotification::dispatch($trigger->bot->id, (string) $chat->chat_id, $message, $photos ?: null);
        }

        Log::info('Уведомления о пересечении поставлены в очередь', [
            'row_id' => $row['id'],
            'trigger_id' => $trigger->id,
        ]);
    }

    public static function buildMessage(Stream $stream, array $data, string $datetime): string
    {
        $timestamp = Carbon::parse($datetime);

        if (($data['mode'] ?? 'fields') === 'template' && !empty($data['template'])) {
            return strtr($data['template'], [
                '{Дата}' => $timestamp->format('d.m.Y'),
                '{Время}' => $timestamp->format('H:i:s'),
                '{Видеопоток}' => trim(($stream->name ?? '') . ' | ' . ($stream->location ?? '')),
            ]);
        }

        $message = 'Пересечение линии!';

        if (!empty($data['date'])) {
            $message .= "\nДата: " . $timestamp->format('d.m.Y');
        }
        if (!empty($data['time'])) {
            $message .= "\nВремя: " . $timestamp->format('H:i:s');
        }
        if (!empty($data['stream'])) {
            $message .= "\nВидеопоток: {$stream->name} | {$stream->location}";
        }

        return $message;
    }

    private function trackRowStats(array $row, string $uid): void
    {
        try {
            $ts = (float) Carbon::parse($row['datetime'])->getTimestamp();
        } catch (Throwable) {
            $ts = 0.0;
        }

        $stat = $this->stats[$uid] ?? ['rows' => 0, 'dt_min' => null, 'dt_max' => null, 'dt_sum' => 0.0];

        if (isset($this->lastRowTs[$uid])) {
            $dt = round(abs($ts - $this->lastRowTs[$uid]), 1);
            $stat['dt_min'] = $stat['dt_min'] === null ? $dt : min($stat['dt_min'], $dt);
            $stat['dt_max'] = $stat['dt_max'] === null ? $dt : max($stat['dt_max'], $dt);
            $stat['dt_sum'] += $dt;
        }

        $this->lastRowTs[$uid] = $ts;
        $stat['rows']++;

        $this->stats[$uid] = $stat;
    }

    private function flushStats(): void
    {
        if ($this->stats === []) {
            return;
        }

        foreach ($this->stats as $uid => $stat) {
            if ($stat['rows'] > 1) {
                $this->stats[$uid]['dt_avg'] = round($stat['dt_sum'] / ($stat['rows'] - 1), 1);
            }
            unset($this->stats[$uid]['dt_sum']);
        }

        Log::info('Статистика обработки пересечений', $this->stats);

        $this->stats = [];
    }
}
