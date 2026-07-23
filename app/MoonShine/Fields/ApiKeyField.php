<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

class ApiKeyField extends Field
{
    protected string $view = 'fields.api-key-field';

    protected int $visibleSuffix = 4;

    protected int $dots = 12;

    public function suffix(int $count): static
    {
        $this->visibleSuffix = $count;

        return $this;
    }

    public function dots(int $count): static
    {
        $this->dots = $count;

        return $this;
    }

    protected function maskKey(string $key): string
    {
        if ($key === '') {
            return '';
        }

        // Telegram-токен вида "8734085105:AAF...": оставляем видимым ID бота до двоеточия.
        if (str_contains($key, ':')) {
            [$prefix, $secret] = explode(':', $key, 2);

            return $prefix . ':' . str_repeat('•', $this->dots) . mb_substr($secret, -$this->visibleSuffix);
        }

        $parts = explode('_', $key);
        $secret = array_pop($parts);
        $prefix = implode('_', $parts);

        $masked = str_repeat('•', $this->dots) . mb_substr($secret, -$this->visibleSuffix);

        return $prefix !== '' ? $prefix . '_' . $masked : $masked;
    }

    protected function buildData(): array
    {
        $key = (string) ($this->toValue() ?? '');

        return [
            'fullKey' => $key,
            'maskedKey' => $this->maskKey($key),
        ];
    }

    protected function resolvePreview(): Renderable|string
    {
        return view($this->view, $this->buildData());
    }

    protected function viewData(): array
    {
        return $this->buildData();
    }
}
