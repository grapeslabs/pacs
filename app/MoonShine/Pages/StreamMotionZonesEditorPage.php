<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use App\MoonShine\Fields\MultiZoneEditorField;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\UI\Components\FormBuilder;

class StreamMotionZonesEditorPage extends Page
{
    public function getTitle(): string
    {
        return 'Редактирование зон';
    }

    public function components(): array
    {
        $resource = $this->getResource();
        $item = $resource->getItem();

        if (is_null($item)) {
            return [];
        }

        $methodUrl = $resource->getAsyncMethodUrl(
            'saveMotionDetectionZones',
            params: ['resourceItem' => $item->getKey()]
        );

        return [
            FormBuilder::make($methodUrl)
                ->fillCast($item, new ModelCaster(Stream::class))
                ->fields([
                    MultiZoneEditorField::make('Редактирование зон детекции движения', 'va_options->motion_detection_zones')
                        ->cancelUrl($resource->getIndexPageUrl())
                        ->saveUrl($resource->getIndexPageUrl())
                        ->disableMultiType()
                        ->disablePolygons()
                        ->saveText('Зоны детекции движения успешно сохранены!')
                ])
                ->hideSubmit()
        ];
    }
}
