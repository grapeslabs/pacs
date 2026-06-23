<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\ActuatorDevice;
use App\Models\GrapeslabsSkudController;
use App\Models\GrapeslabsSkudEvent;
use App\Models\Person;
use App\Models\Stream;
use App\Models\Trigger;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Components\Dashboard\EventsPanel;
use App\MoonShine\Components\Dashboard\InstructionCard;
use App\MoonShine\Components\Dashboard\QuickActions;
use App\MoonShine\Components\Dashboard\RecognitionPanel;
use App\MoonShine\Components\Dashboard\StatCards;
use App\MoonShine\Components\Dashboard\SystemStatus;
use App\MoonShine\Components\Dashboard\WelcomeHero;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\SkudEventResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\VideoStreamResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;

class Dashboard extends Page
{
    protected string $title = 'Главная';

    protected function components(): iterable
    {
        $vaEnabled = (bool) config('services.va.enabled');

        $personUrl = app(PersonResource::class)->getUrl();
        $keyUrl = app(KeyResource::class)->getUrl();
        $streamUrl = app(VideoStreamResource::class)->getUrl();
        $triggerUrl = app(TriggerResource::class)->getUrl();
        $skudUrl = app(SkudEventResource::class)->getUrl();

        $user = auth('moonshine')->user();
        $rawName = trim((string) ($user->name ?? ''));
        if ($rawName === '' || mb_strtolower($rawName) === 'default') {
            $rawName = $user ? (string) strstr((string) $user->email, '@', true) : '';
        }
        $firstName = preg_split('/\s+/u', $rawName)[0] ?? '';

        $recognition = $this->recognition($vaEnabled);

        return [
            Grid::make([
                Column::make([
                    WelcomeHero::make(
                        greeting: $this->greeting(),
                        name: $firstName,
                        today: $this->todayLabel(),
                        streamsUrl: $streamUrl,
                    ),
                ])->columnSpan(6),
                Column::make([
                    QuickActions::make([
                        ['label' => 'Добавить персону', 'url' => $personUrl, 'icon' => '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8"></path><path d="M5 20a7 7 0 0 1 14 0"></path></svg>'],
                        ['label' => 'Добавить ключ', 'url' => $keyUrl, 'icon' => '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 7a4 4 0 1 1-3.5 5.9L4 19.5V21H2.5v-1.5L9.1 13A4 4 0 0 1 14 7z"></path><path d="M15.5 9.5h.01"></path></svg>'],
                        ['label' => 'Добавить камеру', 'url' => $streamUrl, 'icon' => '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h9A1.5 1.5 0 0 1 15 7.5v9A1.5 1.5 0 0 1 13.5 18h-9A1.5 1.5 0 0 1 3 16.5z"></path><path d="M15 10.5 21 7v10l-6-3.5"></path></svg>'],
                        ['label' => 'Добавить триггер', 'url' => $triggerUrl, 'icon' => '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 3 4 14h7l-1 7 9-11h-7z"></path></svg>'],
                    ]),
                ])->columnSpan(6),
            ]),

            StatCards::make($this->stats($vaEnabled, $streamUrl, $personUrl, $triggerUrl, $skudUrl)),

            Grid::make([
                Column::make([
                    EventsPanel::make($this->events($vaEnabled), $skudUrl),
                ])->columnSpan(4),
                Column::make([
                    RecognitionPanel::make(
                        $recognition['items'],
                        $recognition['camerasCount'],
                        $recognition['total'],
                    ),
                    InstructionCard::make(
                        description: 'Как настроить видеопотоки и распознавание лиц, вести базу СКУД, подключать ботов и оборудование, формировать отчёты.',
                        btnUrl: 'https://grapeslabs.ru/projects',
                    ),
                ])->columnSpan(4),
                Column::make([
                    SystemStatus::make($this->system($vaEnabled)),
                ])->columnSpan(4),
            ])->customAttributes(['style' => 'margin-top:20px;']),
        ];
    }

    private function stats(bool $vaEnabled, string $streamUrl, string $personUrl, string $triggerUrl, string $skudUrl): array
    {
        $recToday = $vaEnabled ? VideoAnalyticReport::whereDate('datetime', today())->count() : 0;
        $recYesterday = $vaEnabled ? VideoAnalyticReport::whereDate('datetime', today()->subDay())->count() : 0;

        $skudToday = GrapeslabsSkudEvent::whereDate('datetime', today())->count();
        $skudYesterday = GrapeslabsSkudEvent::whereDate('datetime', today()->subDay())->count();

        return [
            ['label' => 'Камеры онлайн', 'value' => Stream::count(), 'url' => $streamUrl,
                'trend' => $this->delta(Stream::whereDate('created_at', today())->count())],
            ['label' => 'Распознавания за сутки', 'value' => $recToday, 'url' => $skudUrl,
                'trend' => $this->pctDelta($recToday, $recYesterday)],
            ['label' => 'События СКУД сегодня', 'value' => $skudToday, 'url' => $skudUrl,
                'trend' => $this->pctDelta($skudToday, $skudYesterday)],
            ['label' => 'Персоны в базе', 'value' => Person::count(), 'url' => $personUrl,
                'trend' => $this->delta(Person::whereDate('created_at', today())->count())],
            ['label' => 'Активные триггеры', 'value' => Trigger::where('is_active', true)->count(), 'url' => $triggerUrl,
                'trend' => ['dir' => 'none', 'text' => 'без изменений']],
        ];
    }

    private function delta(int $n): array
    {
        return $n > 0 ? ['dir' => 'up', 'text' => "+{$n} за сутки"] : ['dir' => 'none', 'text' => 'без изменений'];
    }

    private function pctDelta(int $today, int $yesterday): array
    {
        if ($yesterday <= 0) {
            return $today > 0 ? ['dir' => 'up', 'text' => '+100% за сутки'] : ['dir' => 'none', 'text' => 'без изменений'];
        }
        $pct = (int) round(($today - $yesterday) / $yesterday * 100);
        if ($pct === 0) {
            return ['dir' => 'none', 'text' => 'без изменений'];
        }

        return ['dir' => $pct > 0 ? 'up' : 'down', 'text' => ($pct > 0 ? '+' : '') . "{$pct}% за сутки"];
    }

    private function events(bool $vaEnabled): array
    {
        if (! $vaEnabled) {
            return [];
        }

        return VideoAnalyticReport::query()
            ->orderByDesc('datetime')
            ->limit(15)
            ->get()
            ->map(function (VideoAnalyticReport $r) {
                $person = $r->is_unknown ? null : Person::where('grapesva_uuid', $r->person_photobank_id)->first();
                $snapshot = $r->data['snapshot_path'] ?? null;

                return [
                    'is_unknown' => (bool) $r->is_unknown,
                    'title' => $r->is_unknown ? 'Неопознанное лицо' : 'Распознан: ' . ($person?->getFullName() ?: 'неизвестно'),
                    'location' => Stream::where('uid', $r->camera_id)->value('name') ?: 'Камера',
                    'time' => optional($r->datetime)->format('H:i:s'),
                    'avatar' => $snapshot ? Storage::disk('analytic')->url('thumbnails/' . basename($snapshot)) : null,
                    'status' => $r->is_unknown ? 'Неизвестен' : 'Опознан',
                ];
            })
            ->all();
    }

    private function recognition(bool $vaEnabled): array
    {
        if (! $vaEnabled) {
            return ['items' => [], 'camerasCount' => 0, 'total' => 0];
        }

        $rows = VideoAnalyticReport::whereDate('datetime', today())
            ->selectRaw('camera_id, count(*) as cnt')
            ->groupBy('camera_id')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get();

        $streams = Stream::whereIn('uid', $rows->pluck('camera_id')->filter())->get()->keyBy('uid');

        $items = $rows->map(function ($row) use ($streams) {
            $stream = $streams->get($row->camera_id);
            $aiOn = (bool) ($stream?->va_options['global_enable'] ?? false);

            return [
                'camera' => $stream?->name ?: 'Камера',
                'location' => ($stream?->location ?: 'Объект') . ' · распознавание ' . ($aiOn ? 'вкл.' : 'выкл.'),
                'count' => (int) $row->cnt,
            ];
        })->all();

        return [
            'items' => $items,
            'camerasCount' => $this->aiEnabledStreamsCount(),
            'total' => VideoAnalyticReport::whereDate('datetime', today())->count(),
        ];
    }

    private function system(bool $vaEnabled): array
    {
        $diskTotal = @disk_total_space('/') ?: 0;
        $diskFree = @disk_free_space('/') ?: 0;
        $diskUsed = $diskTotal - $diskFree;

        $camerasTotal = Stream::count();
        $aiCameras = $vaEnabled ? $this->aiEnabledStreamsCount() : 0;
        $equipmentTotal = ActuatorDevice::count() + GrapeslabsSkudController::count();

        return [
            'equipment' => ['online' => $equipmentTotal, 'total' => $equipmentTotal],
            'ai' => ['cameras_on' => $aiCameras, 'cameras_total' => $camerasTotal, 'working' => $aiCameras > 0],
            'cameras' => ['online' => $camerasTotal, 'not_responding' => 0],
            'storage' => [
                'used' => $diskUsed,
                'total' => $diskTotal,
                'pct' => $diskTotal > 0 ? (int) round($diskUsed / $diskTotal * 100) : 0,
            ],
        ];
    }

    private function aiEnabledStreamsCount(): int
    {
        return Stream::whereRaw("(va_options->>'global_enable') in ('1', 'true')")->count();
    }

    private function greeting(): string
    {
        $h = (int) now()->format('G');
        if ($h < 6) return 'Доброй ночи';
        if ($h < 12) return 'Доброе утро';
        if ($h < 18) return 'Добрый день';
        return 'Добрый вечер';
    }

    private function todayLabel(): string
    {
        Carbon::setLocale('ru');

        return now()->locale('ru')->isoFormat('D MMMM, dddd');
    }
}
