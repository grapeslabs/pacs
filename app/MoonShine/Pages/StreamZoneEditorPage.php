<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use App\MoonShine\Fields\ZoneEditorField;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\UI\Components\FormBuilder;

class StreamZoneEditorPage extends Page
{
    public function getTitle(): string
    {
        return 'Редактирование зоны';
    }

    public function components(): array
    {
        $resource = $this->getResource();
        $item = $resource->getItem();

        if (is_null($item)) {
            return [];
        }

        $methodUrl = $resource->getAsyncMethodUrl(
            'saveZone',
            params: ['resourceItem' => $item->getKey()]
        );

        return [
            FormBuilder::make($methodUrl)
                ->fillCast($item, new ModelCaster(Stream::class))
                ->fields([
                    ZoneEditorField::make('Редактирование зоны', 'va_options->face_detection_zone')
                        ->cancelUrl($resource->getIndexPageUrl())
                        ->saveUrl($resource->getIndexPageUrl())
                        ->saveText('Зона поиска лица успешно сохранена!')
                ])
                ->hideSubmit()
        ];
    }
}
