<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

final class MultiZoneEditorField extends Field
{
    protected string $view = 'fields.zones-editor-field';

    protected ?string $cancelUrl = null;

    protected ?string $saveUrl = null;

    protected string $saveText = 'Настройки сохранены';

    protected bool $allowRectangles = true;

    protected bool $allowLines = true;

    protected bool $allowPolygons = true;

    protected bool $multiType = true;

    protected string $colorRectangles = '#3b82f6';

    protected string $colorLines = '#ef4444';

    protected string $colorPolygons = '#22c55e';

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

    public function cancelUrl(string $url): self
    {
        $this->cancelUrl = $url;

        return $this;
    }

    public function saveUrl(string $url): self
    {
        $this->saveUrl = $url;

        return $this;
    }

    public function saveText(string $text): self
    {
        $this->saveText = $text;

        return $this;
    }

    public function disableRectangles(): self
    {
        $this->allowRectangles = false;

        return $this;
    }

    public function disableLines(): self
    {
        $this->allowLines = false;

        return $this;
    }

    public function disablePolygons(): self
    {
        $this->allowPolygons = false;

        return $this;
    }

    public function disableMultiType(): self
    {
        $this->multiType = false;

        return $this;
    }

    public function getCancelUrl(): ?string
    {
        return $this->cancelUrl;
    }

    public function getSaveUrl(): ?string
    {
        return $this->saveUrl;
    }

    public function getSaveText(): string
    {
        return $this->saveText;
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
            'config' => [
                'allowRectangles' => $this->allowRectangles,
                'allowLines' => $this->allowLines,
                'allowPolygons' => $this->allowPolygons,
                'multiType' => $this->multiType,
                'colors' => [
                    'rectangles' => $this->colorRectangles,
                    'lines' => $this->colorLines,
                    'polygons' => $this->colorPolygons,
                ],
            ],
        ];
    }
}
