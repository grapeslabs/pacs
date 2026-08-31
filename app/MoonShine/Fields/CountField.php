<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

class CountField extends Field
{
    protected string $view = 'fields.count-field';

    protected function resolvePreview(): Renderable|string
    {
        return view($this->view, ['count' => (int) ($this->toFormattedValue() ?? $this->toValue() ?? 0)]);
    }

    protected function viewData(): array
    {
        return ['count' => (int) ($this->toFormattedValue() ?? $this->toValue() ?? 0)];
    }
}
