<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use http\Env\Request;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class StreamPlayer extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            route('moonshine.resource.page', ['video-stream-resource', 'streams']) => 'Видеопотоки',
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
        return [
            Preview::make('', 'name', function() use ($stream) {
                return view('components.stream-player', ['item' => $stream]);
            } )
        ];
    }
}
