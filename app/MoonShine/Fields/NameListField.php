<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

/**
 * Вертикальный список строк (по одной в строке).
 *
 * Пример: NameListField::make('Персоны', 'people', fn($item) => $item->people->map->getFullName()->all())
 */
class NameListField extends Field
{
    protected string $view = 'fields.name-list-field';

    protected function resolveNames(): array
    {
        $value = $this->toFormattedValue() ?? $this->toValue();

        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        return is_array($value)
            ? array_values(array_filter(array_map('strval', $value), fn ($v) => trim($v) !== ''))
            : [];
    }

    protected function resolvePreview(): Renderable|string
    {
        $names = $this->resolveNames();

        if (empty($names)) {
            return '—';
        }

        return view($this->view, ['names' => $names]);
    }

    protected function viewData(): array
    {
        return ['names' => $this->resolveNames()];
    }
}
