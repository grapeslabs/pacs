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
    protected ?array $showWhenData = null;

    public function icon(string $icon): static
    {
        $this->iconValue = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->iconValue;
    }

    public function showWhen(string $column, string $operator, mixed $value): static
    {
        $this->showWhenData = ['column' => $column, 'operator' => $operator, 'value' => $value];

        return $this;
    }

    public function hasShowWhen(): bool
    {
        return $this->showWhenData !== null;
    }

    public function getShowWhenData(): ?array
    {
        return $this->showWhenData;
    }

    protected function viewData(): array
    {
        return [
            'element' => $this,
        ];
    }
}
