<?php

declare(strict_types=1);

namespace App\MoonShine\Decorations;

use MoonShine\Contracts\UI\HasFieldsContract;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Traits\WithFields;

class FeatureBox extends MoonShineComponent implements HasFieldsContract
{
    use WithFields;

    protected string $view = 'components.feature-box';

    protected ?string $iconValue = null;

    public function icon(string $icon): static
    {
        $this->iconValue = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->iconValue;
    }

    protected function viewData(): array
    {
        return [
            'element' => $this,
        ];
    }
}
