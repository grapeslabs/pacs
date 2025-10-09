<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Text;

class Streams extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Видеопотоки';
    }

    public function assets(): array
    {
        return [
            Js::make('https://cdn.jsdelivr.net/npm/hls.js@latest'),
            Css::make(asset('css/streams.css')),
            Js::make(asset('js/streams.js')),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): array
    {
        $streams = Stream::all()->sortByDesc('created_at');
        $columns = [];

        foreach ($streams as $stream) {
            $columns[] =  Column::make([
                Box::make('')->customView('components.stream-box', ['stream' => $stream])
            ])->columnSpan(4);
        }
        $grid = Grid::make($columns);

        return [
            ActionButton::make('Добавить поток',
                route('moonshine.resource.page',
                    ['video-stream-resource','form-page']
                ))->primary(),
            ActionButton::make('Список потоков',
                route('moonshine.resource.page',
                    ['video-stream-resource','custom-index-page']
                ))->primary(),
            Divider::make(),
            $grid
        ];
    }
}
