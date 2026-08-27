<?php

namespace App\Jobs;

use App\Models\Person;
use App\Models\PresenceReport;
use App\Models\VideoAnalyticReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratePresenceReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const COLLAPSE_GAP_SECONDS = 60;

    public int $timeout = 1800;

    public function __construct(
        protected int $reportId
    ) {}

    public function handle(): void
    {
        $report = PresenceReport::find($this->reportId);
        if (!$report) {
            Log::warning("PresenceReport #{$this->reportId} not found");
            return;
        }

        $report->update(['status' => PresenceReport::STATUS_PROCESSING]);

        try {
            $result = $this->build($report->params);
            $report->update([
                'status' => PresenceReport::STATUS_DONE,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('PresenceReport generation failed', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage(),
            ]);
            $report->update([
                'status' => PresenceReport::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function build(array $params): array
    {
        $type = $params['type'] ?? 'day';
        $entryUid = $params['entry_camera'];
        $exitUid = $params['exit_camera'];
        $from = Carbon::parse($params['date_from'])->startOfDay();
        $to = Carbon::parse($params['date_to'])->endOfDay();
        $breakSeconds = (int) ($params['break_minutes'] ?? 30) * 60;

        $uuidFilter = $this->resolveUuidFilter($params);

        $query = VideoAnalyticReport::query()
            ->whereIn('camera_id', [$entryUid, $exitUid])
            ->whereNotNull('person_photobank_id')
            ->where('is_unknown', false)
            ->whereBetween('datetime', [$from, $to])
            ->orderBy('person_photobank_id')
            ->orderBy('datetime')
            ->select(['person_photobank_id', 'camera_id', 'datetime']);

        if ($uuidFilter !== null) {
            $query->whereIn('person_photobank_id', $uuidFilter);
        }

        $persons = [];
        $periodKeys = [];
        $currentUuid = null;
        $passes = [];

        $flush = function () use (&$persons, &$periodKeys, &$currentUuid, &$passes, $entryUid, $exitUid, $breakSeconds, $type) {
            if ($currentUuid === null || empty($passes)) {
                $passes = [];
                return;
            }
            $this->pairAndBucket($currentUuid, $passes, $breakSeconds, $type, $persons, $periodKeys);
            $passes = [];
        };

        foreach ($query->cursor() as $event) {
            $uuid = $event->person_photobank_id;
            if ($uuid !== $currentUuid) {
                $flush();
                $currentUuid = $uuid;
            }

            $role = $event->camera_id === $entryUid ? 'entry' : 'exit';
            $time = $event->datetime instanceof Carbon ? $event->datetime : Carbon::parse($event->datetime);

            $last = end($passes);
            if ($last !== false
                && $last['role'] === $role
                && $time->diffInSeconds($last['time']) <= self::COLLAPSE_GAP_SECONDS) {
                continue;
            }
            $passes[] = ['role' => $role, 'time' => $time];
        }
        $flush();

        if ($uuidFilter !== null) {
            foreach ($uuidFilter as $uuid) {
                if (!isset($persons[$uuid])) {
                    $persons[$uuid] = ['cells' => [], 'total_time' => 0, 'total_count' => 0];
                }
            }
        }

        $personModels = Person::whereIn('grapesva_uuid', array_keys($persons))->get();
        $names = $personModels->mapWithKeys(fn ($p) => [$p->grapesva_uuid => $p->getFullName()]);
        $photos = $personModels->mapWithKeys(function ($p) {
            $first = is_array($p->photo) && count($p->photo) > 0 ? $p->photo[0] : null;
            return [$p->grapesva_uuid => $first ? Storage::disk('public')->url($first) : null];
        });

        uasort($periodKeys, fn ($a, $b) => $a['order'] <=> $b['order']);
        $periods = [];
        foreach ($periodKeys as $key => $meta) {
            $periods[] = ['key' => $key, 'label' => $meta['label'], 'sublabel' => $meta['sublabel']];
        }

        $rows = [];
        foreach ($persons as $uuid => $p) {
            $rows[] = [
                'name' => $names[$uuid] ?? $uuid,
                'photo' => $photos[$uuid] ?? null,
                'cells' => $p['cells'],
                'total_time' => $p['total_time'],
                'total_count' => $p['total_count'],
            ];
        }
        usort($rows, fn ($a, $b) => $b['total_time'] <=> $a['total_time']);

        return [
            'type' => $type,
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'break_minutes' => (int) ($params['break_minutes'] ?? 30),
            'entry_camera_name' => $params['entry_camera_name'] ?? $entryUid,
            'exit_camera_name' => $params['exit_camera_name'] ?? $exitUid,
            'periods' => $periods,
            'persons' => $rows,
        ];
    }

    protected function pairAndBucket(string $uuid, array $passes, int $breakSeconds, string $type, array &$persons, array &$periodKeys): void
    {
        if (!isset($persons[$uuid])) {
            $persons[$uuid] = ['cells' => [], 'total_time' => 0, 'total_count' => 0];
        }

        $openEntry = null;

        $markBroken = function (Carbon $entryTime) use ($uuid, $type, &$persons, &$periodKeys) {
            [$key, $label, $sublabel, $order] = $this->periodOf($entryTime, $type);
            $this->ensurePeriod($periodKeys, $key, $label, $sublabel, $order);
            $this->ensureCell($persons[$uuid]['cells'], $key);
            $persons[$uuid]['cells'][$key]['broken'][] = $entryTime->format('d.m.Y H:i');
        };

        foreach ($passes as $pass) {
            if ($pass['role'] === 'entry') {
                if ($openEntry !== null) {
                    $markBroken($openEntry);
                }
                $openEntry = $pass['time'];
                continue;
            }

            if ($openEntry !== null) {
                $gap = $pass['time']->diffInSeconds($openEntry);
                if ($gap <= $breakSeconds) {
                    [$key, $label, $sublabel, $order] = $this->periodOf($openEntry, $type);
                    $this->ensurePeriod($periodKeys, $key, $label, $sublabel, $order);
                    $this->ensureCell($persons[$uuid]['cells'], $key);
                    $persons[$uuid]['cells'][$key]['time'] += $gap;
                    $persons[$uuid]['cells'][$key]['count'] += 1;
                    $persons[$uuid]['total_time'] += $gap;
                    $persons[$uuid]['total_count'] += 1;
                } else {
                    $markBroken($openEntry);
                }
                $openEntry = null;
            }
        }

        if ($openEntry !== null) {
            $markBroken($openEntry);
        }
    }

    protected function ensureCell(array &$cells, string $key): void
    {
        if (!isset($cells[$key])) {
            $cells[$key] = ['time' => 0, 'count' => 0, 'broken' => []];
        }
    }

    protected function ensurePeriod(array &$periodKeys, string $key, string $label, string $sublabel, int $order): void
    {
        if (!isset($periodKeys[$key])) {
            $periodKeys[$key] = ['label' => $label, 'sublabel' => $sublabel, 'order' => $order];
        }
    }

    protected function periodOf(Carbon $time, string $type): array
    {
        $weekDays = ['', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $months = ['', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

        return match ($type) {
            'week' => [
                $time->isoFormat('GGGG-[W]WW'),
                'Неделя №' . $time->isoWeek(),
                $time->copy()->startOfWeek()->format('d.m') . '–' . $time->copy()->endOfWeek()->format('d.m'),
                (int) $time->copy()->startOfWeek()->timestamp,
            ],
            'month' => [
                $time->format('Y-m'),
                $months[(int) $time->format('n')] . ' ' . $time->format('Y'),
                '',
                (int) $time->copy()->startOfMonth()->timestamp,
            ],
            'year' => [
                $time->format('Y'),
                $time->format('Y'),
                '',
                (int) $time->copy()->startOfYear()->timestamp,
            ],
            default => [
                $time->format('Y-m-d'),
                $time->format('d.m') . '(' . $weekDays[$time->dayOfWeekIso] . ')',
                '',
                (int) $time->copy()->startOfDay()->timestamp,
            ],
        };
    }

    protected function resolveUuidFilter(array $params): ?array
    {
        $personIds = array_filter((array) ($params['person_id'] ?? []));
        $tagIds = array_filter((array) ($params['tag_id'] ?? []));

        if (empty($personIds) && empty($tagIds)) {
            return null;
        }

        return Person::query()
            ->whereNotNull('grapesva_uuid')
            ->where(function ($q) use ($personIds, $tagIds) {
                if (!empty($personIds)) {
                    $q->orWhereIn('id', $personIds);
                }
                if (!empty($tagIds)) {
                    $q->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds));
                }
            })
            ->pluck('grapesva_uuid')
            ->all();
    }
}
