<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

/**
 * Ячейка тегов: набор pill-чипов.
 *
 * Пример: TagsField::make('Теги', 'tags', fn($item) => $item->tags->pluck('name')->all())
 */
class TagsField extends Field
{
    protected string $view = 'fields.tags-field';

    protected function resolveTags(): array
    {
        $value = $this->toFormattedValue() ?? $this->toValue();

        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        return is_array($value) ? array_values(array_filter(array_map('strval', $value), fn($v) => $v !== '')) : [];
    }

    protected function resolvePreview(): Renderable|string
    {
        $tags = $this->resolveTags();

        if (empty($tags)) {
            return '—';
        }

        return view($this->view, ['tags' => $tags]);
    }

    protected function viewData(): array
    {
        return ['tags' => $this->resolveTags()];
    }
}
