<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\CarPassageEvent;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Preview;

class StreamPlayer extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            route('moonshine.resource.page', ['video-stream-resource', 'video-stream-index-page']) => 'Видеопотоки',
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        $stream = $this->getResource()->getItem();
        return "{$stream->name} | {$stream->location}";
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): array
    {
        $stream = $this->getResource()->getItem();

        $events = collect();
        if ($stream) {
            if (config('services.va.enabled')) {
                try {
                    $faces = VideoAnalyticReport::with('person')
                        ->where('camera_id', $stream->uid)
                        ->whereBetween('datetime', [today()->startOfDay(), today()->endOfDay()])
                        ->orderByDesc('datetime')
                        ->limit(50)
                        ->get()
                        ->map(function ($e) {
                            $person = $e->person?->getFullName();

                            return [
                                'type'     => 'face',
                                'title'    => $person ? 'Распознана персона' : 'Распознан неизвестный',
                                'sub'      => $person,
                                'datetime' => $e->datetime,
                            ];
                        });

                    $events = $events->merge($faces);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            try {
                $plates = CarPassageEvent::where('camera_id', $stream->uid)
                    ->whereBetween('recognized_at', [today()->startOfDay(), today()->endOfDay()])
                    ->orderByDesc('recognized_at')
                    ->limit(50)
                    ->get()
                    ->map(fn ($e) => [
                        'type'     => 'plate',
                        'title'    => 'Распознан номер',
                        'sub'      => $e->plate_text ?: null,
                        'datetime' => $e->recognized_at,
                    ]);

                $events = $events->merge($plates);
            } catch (\Throwable $e) {
                report($e);
            }

            $events = $events
                ->sortByDesc('datetime')
                ->take(50)
                ->values();
        }

        $editUrl = $this->getResource()->getFormPageUrl($stream->getKey(), params: [
            '_async_form' => true,
        ], fragment: 'crud-form');
        $indexUrl = route('moonshine.resource.page', ['video-stream-resource', 'video-stream-index-page']);

        return [
            $this->playerHeader($stream, $editUrl, $indexUrl),

            Grid::make([
                Column::make([
                    Preview::make('', 'name', fn() => view('components.stream-player', ['item' => $stream])),
                ])->columnSpan(9),

                Column::make([
                    Preview::make('', 'name', fn() => view('components.stream-params', [
                        'stream' => $stream,
                        'events' => $events,
                    ])),
                ])->columnSpan(3),
            ]),
        ];
    }

    private function playerHeader($stream, string $editUrl, string $indexUrl): string
    {
        $name = e($stream->name);
        $location = e($stream->location ?: '');
        $editUrlJs = addslashes($editUrl);
        $titleJs = addslashes((string) $stream->name);

        $settingsClick = "axios.get('{$editUrlJs}').then(function(r){"
            . "let c=document.querySelector('#universal-safe-modal_content');"
            . "if(c){c.innerHTML=r.data;"
            . "window.dispatchEvent(new CustomEvent('modal-toggled',{detail:{id:'universal-safe-modal',title:'{$titleJs}',subtitle:'Настройки камеры'}}));}});";

        $btnStyle = 'display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:9999px;background:#fff;color:#4E4E61;border:1px solid #D7D0EF;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;font-family:inherit;';

        $listIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"></path></svg>';
        $gearIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-pv-ic="1"><path d="M17 13.25C19.0711 13.25 20.75 14.9289 20.75 17C20.75 19.0711 19.0711 20.75 17 20.75C15.1858 20.75 13.6727 19.4617 13.3252 17.75H5C4.58579 17.75 4.25 17.4142 4.25 17C4.25 16.5858 4.58579 16.25 5 16.25H13.3252C13.6727 14.5383 15.1858 13.25 17 13.25ZM17 14.75C15.7574 14.75 14.75 15.7574 14.75 17C14.75 18.2426 15.7574 19.25 17 19.25C18.2426 19.25 19.25 18.2426 19.25 17C19.25 15.7574 18.2426 14.75 17 14.75ZM7 3.25C8.81421 3.25 10.3273 4.53834 10.6748 6.25H19C19.4142 6.25 19.75 6.58579 19.75 7C19.75 7.41421 19.4142 7.75 19 7.75H10.6748C10.3273 9.46166 8.81421 10.75 7 10.75C4.92893 10.75 3.25 9.07107 3.25 7C3.25 4.92893 4.92893 3.25 7 3.25ZM7 4.75C5.75736 4.75 4.75 5.75736 4.75 7C4.75 8.24264 5.75736 9.25 7 9.25C8.24264 9.25 9.25 8.24264 9.25 7C9.25 5.75736 8.24264 4.75 7 4.75Z" fill="currentColor"></path></svg>';

        return '<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">'
            . '<div style="min-width:0;">'
            . '<h2 style="font-size:22px;font-weight:700;color:#1a1a2e;margin:0;line-height:1.2;">' . $name . '</h2>'
            . ($location !== '' ? '<p style="font-size:13px;color:#7F7F9D;margin:4px 0 0;">' . $location . '</p>' : '')
            . '</div>'
            . '<div style="display:flex;align-items:center;gap:14px;flex-shrink:0;">'
            . '<a href="' . $indexUrl . '" style="' . $btnStyle . '">' . $listIcon . 'К списку</a>'
            . '<button type="button" x-data @click="' . htmlspecialchars($settingsClick, ENT_QUOTES) . '" style="' . $btnStyle . '">' . $gearIcon . 'Настройки</button>'
            . '</div>'
            . '</div>';
    }
}
