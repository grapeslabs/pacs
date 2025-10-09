<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Bot;
use App\Models\Person;
use App\Models\Stream;
use App\Models\Trigger;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Components\EventLogComponent;
use App\MoonShine\Components\InfoPanel;
use App\MoonShine\Resources\BotResource;
use App\MoonShine\Resources\EventReportResource;
use App\MoonShine\Resources\PeopleReportResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\VideoStreamResource;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

class Dashboard extends Page
{

    protected string $title = 'Главная';

    protected function components(): iterable
	{
        $streamUrl = app(VideoStreamResource::class)->getUrl();
        $eventReportUrl = app(EventReportResource::class)->getUrl();
        $personUrl = app(PersonResource::class)->getUrl();
        $triggerUrl = app(TriggerResource::class)->getUrl();
        $botUrl = app(BotResource::class)->getUrl();

        $eventLogsRaw = VideoAnalyticReport::query()->limit(10)->orderByDesc('id')->get();
        $eventLogs = $eventLogsRaw->map(fn($log) => [
            'icon' => Storage::disk('analytic')->url('thumbnails/' . basename($log->data['snapshot_path'])),
            'title' => 'Распознано лицо: ' . ($log->is_unknown?'Неопознано':Person::find($log->person_photobank_id)?->getFullName()??'Неопознано'),
            'subtitle' => Stream::where('uid', $log->camera_id)->first()?->name,
            'isError' => false,
        ]);

		return [
            Grid::make([
                Column::make([])->columnSpan(1),
                Column::make([
                    ValueMetric::make('Активные камеры')
                        ->value(Stream::query()->count())
                        ->icon(file_get_contents(asset('icons/menu-video.svg')), true)
                        ->customAttributes([
                            'onclick' => "window.location.href='{$streamUrl}'",
                            'class' => 'cursor-pointer hover:shadow-lg transition-all',
                        ])
                ])->columnSpan(2),
                Column::make([
                    ValueMetric::make('Распознавание лиц')
                        ->value(VideoAnalyticReport::query()->count())
                        ->icon(file_get_contents(asset('icons/menu-reports.svg')), true)
                        ->customAttributes([
                            'onclick' => "window.location.href='{$eventReportUrl}'",
                            'class' => 'cursor-pointer hover:shadow-lg transition-all',
                        ])
                ])->columnSpan(2),
                Column::make([
                    ValueMetric::make('Персоны')
                        ->value(Person::all()->count())
                        ->icon(file_get_contents(asset('icons/menu-skud.svg')), true)
                        ->customAttributes([
                            'onclick' => "window.location.href='{$personUrl}'",
                            'class' => 'cursor-pointer hover:shadow-lg transition-all',
                        ])
                ])->columnSpan(2),
                Column::make([
                    ValueMetric::make('Триггеры')
                        ->value(Trigger::query()->count())
                        ->icon(file_get_contents(asset('icons/menu-trigger.svg')), true)
                        ->customAttributes([
                            'onclick' => "window.location.href='{$triggerUrl}'",
                            'class' => 'cursor-pointer hover:shadow-lg transition-all',
                        ])
                ])->columnSpan(2),
                Column::make([
                    ValueMetric::make('Боты')
                        ->value(Bot::all()->count())
                        ->icon(file_get_contents(asset('icons/menu-bot.svg')), true)
                        ->customAttributes([
                            'onclick' => "window.location.href='{$botUrl}'",
                            'class' => 'cursor-pointer hover:shadow-lg transition-all',
                        ])
                ])->columnSpan(2),
            ]),
            Divider::make(),
            Grid::make([
                Column::make([])->columnSpan(1),
                Column::make([
                    Box::make('Недавние события', [
                        EventLogComponent::make($eventLogs)
                            ->customAttributes([
                                'onclick' => "window.location.href='{$eventReportUrl}'",
                                'class' => 'cursor-pointer hover:shadow-lg transition-all',
                            ])
                    ])->class('cursor-pointer hover:shadow-lg transition-all')
                ])->columnSpan(4),
                Column::make([
                    InfoPanel::make('Инструкция Pacs')
                        ->description('Актуальное руководство по использованию системы: настройка интерфейса, ботов и оборудования СКУД.')
                        ->icon(asset('icons/menu-guide.svg'))
                        ->btnText('Перейти к инструкции')
                        ->btnUrl('https://grapeslabs.ru/projects')
                ])->columnSpan(6)
            ])
        ];
	}
}
