<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Image;

class PhotoField extends Image
{
    protected string $view = 'fields.photo-field';

    protected function prepareFill(array $raw = [], mixed $casted = null): mixed
    {
        $this->multiple();
        return parent::prepareFill($raw, $casted);
    }

    protected function viewData(): array
    {
        return [
            'element' => $this,
        ];
    }
}
