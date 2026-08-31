<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

/**
 * Ячейка «Получатели»: иконка + название первого получателя и «+N».
 *
 * Пример: RecipientsField::make('Получатели', 'chats', fn($item) => $item->chats->pluck('name')->all())
 */
class RecipientsField extends Field
{
    protected string $view = 'fields.recipients-field';

    protected function resolveNames(): array
    {
        $value = $this->toFormattedValue() ?? $this->toValue();

        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        return is_array($value) ? array_values(array_filter(array_map('strval', $value), fn($v) => $v !== '')) : [];
    }

    protected function resolvePreview(): Renderable|string
    {
        $names = $this->resolveNames();

        if (empty($names)) {
            return '—';
        }

        return view($this->view, [
            'first' => $names[0],
            'more' => count($names) - 1,
        ]);
    }

    protected function viewData(): array
    {
        $names = $this->resolveNames();

        return [
            'first' => $names[0] ?? '',
            'more' => max(0, count($names) - 1),
        ];
    }
}
