<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Image;
use Illuminate\Support\Facades\Storage;

class PhotoField extends Image
{
    protected string $view = 'fields.photo-field';
    protected int $limitCount = 30;

    public function limit(int $count): static
    {
        $this->limitCount = $count;
        return $this;
    }
    public function viewData(): array
    {
        $value = $this->toValue();
        $items = [];
        if (!empty($value)) {
            $paths = $this->isMultiple()
                ? (is_string($value) ? json_decode($value, true) : $value)
                : [$value];

            $paths = is_array($paths) ? $paths : [];

            foreach ($paths as $path) {
                if (is_string($path) && $path !== '') {
                    $items[] = [
                        'path' => $path,
                        'url'  => Storage::disk($this->getDisk())->url($path),
                    ];
                }
            }
        }

        return [
            ...parent::viewData(),
            'limitCount' => $this->limitCount,
            'photoItems' => $items,
            'element' => $this,
        ];
    }
}
