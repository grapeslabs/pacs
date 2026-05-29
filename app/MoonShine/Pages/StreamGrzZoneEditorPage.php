<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use App\MoonShine\Fields\SingleZoneEditorField;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\UI\Components\FormBuilder;

class StreamGrzZoneEditorPage extends Page
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
            'savePlateDetectionZone',
            params: ['resourceItem' => $item->getKey()]
        );

        return [
            FormBuilder::make($methodUrl)
                ->fillCast($item, new ModelCaster(Stream::class))
                ->fields([
                    SingleZoneEditorField::make('Редактирование зоны', 'va_options->plate_recognition_zone')
                        ->cancelUrl($resource->getIndexPageUrl())
                        ->saveUrl($resource->getIndexPageUrl())
                        ->saveText('Зона распознавания ГРЗ успешно сохранена!')
                ])
                ->hideSubmit()
        ];
    }
}
