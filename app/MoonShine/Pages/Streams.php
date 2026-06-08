<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use App\MoonShine\Components\StreamGridComponent;
use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Flex;

class Streams extends CustomIndexPage
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
        return [
            Flex::make([
                $this->getCustomCreateButton(),
                ActionButton::make('Список потоков',
                    route('moonshine.resource.page',
                        ['video-stream-resource', 'custom-index-page']
                    ))->primary(),
            ])->justifyAlign('start'),
            Divider::make(),
            StreamGridComponent::make()
                ->items(Stream::all()->sortBy('created_at'))
                ->editUrlResolver(
                    fn($item) => $this->getResource()->getFormPageUrl($item->getKey(), params:[
                        '_component_name' => $this->getResource()->getListComponentName(),
                        '_async_form' => true,
                    ], fragment: 'crud-form')
                ),
        ];
    }
}
