<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

final class MultiZonePreviewField extends Field
{
    protected string $view = 'fields.zones-preview-field';

    protected ?string $setupUrl = null;

    protected string $colorRectangles = '#3b82f6';

    protected string $colorLines = '#ef4444';

    protected string $colorPolygons = '#22c55e';

    public function setupUrl(string $url): self
    {
        $this->setupUrl = $url;

        return $this;
    }

    public function colorRectangles(string $color): self
    {
        $this->colorRectangles = $color;

        return $this;
    }

    public function colorLines(string $color): self
    {
        $this->colorLines = $color;

        return $this;
    }

    public function colorPolygons(string $color): self
    {
        $this->colorPolygons = $color;

        return $this;
    }

    protected function prepareFill(array $raw = [], ?DataWrapperContract $casted = null): mixed
    {
        $value = data_get($raw, str_replace('->', '.', $this->getColumn()));

        if (!is_null($value) && $value !== false) {
            return $value;
        }

        return parent::prepareFill($raw, $casted);
    }

    public function viewData(): array
    {
        return [
            'element' => $this,
            'item' => $this->getData()?->getOriginal(),
            'setupUrl' => $this->setupUrl,
            'colorRectangles' => $this->colorRectangles,
            'colorLines' => $this->colorLines,
            'colorPolygons' => $this->colorPolygons,
        ];
    }
}
