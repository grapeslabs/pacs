<?php

namespace App\MoonShine\Fields;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

class ColoredSelectField extends Field
{
    protected string $view = 'fields.colored-select-field';

    protected array $coloredOptions = [];
    protected string $placeholder = '';
    protected array $customClientRules = [];
    protected mixed $defaultValue = null;

    protected static array $colorPresets = [
        'green'  => ['dot' => '#22c55e', 'bg' => '#dcfce7', 'text' => '#166534'],
        'yellow' => ['dot' => '#eab308', 'bg' => '#fef9c3', 'text' => '#854d0e'],
        'orange' => ['dot' => '#f97316', 'bg' => '#ffedd5', 'text' => '#7c2d12'],
        'pink'   => ['dot' => '#f43f5e', 'bg' => '#ffe4e6', 'text' => '#9f1239'],
        'red'    => ['dot' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#7f1d1d'],
        'blue'   => ['dot' => '#3b82f6', 'bg' => '#dbeafe', 'text' => '#1e40af'],
        'purple' => ['dot' => '#a855f7', 'bg' => '#f3e8ff', 'text' => '#6b21a8'],
        'gray'   => ['dot' => '#6b7280', 'bg' => '#f3f4f6', 'text' => '#374151'],
    ];

    /**
     * Options format:
     *   ['value' => ['label' => 'Text', 'color' => 'green']]
     *   ['value' => ['label' => 'Text', 'color' => '#ff5733']]
     *   ['value' => 'Text']  — renders with default gray
     */
    public function options(array $options): static
    {
        $this->coloredOptions = $options;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function required(Closure|bool|string|null $condition = null, string $message = 'Обязательное поле'): static
    {
        if (is_string($condition)) {
            $message = $condition;
            $condition = null;
        }

        $this->customClientRules[] = ['type' => 'required', 'message' => $message];

        return parent::required($condition);
    }

    protected function resolveColorStyles(string $color): array
    {
        if (isset(static::$colorPresets[$color])) {
            return static::$colorPresets[$color];
        }

        if (str_starts_with($color, '#') && strlen($color) === 7) {
            $hex = ltrim($color, '#');
            $r   = hexdec(substr($hex, 0, 2));
            $g   = hexdec(substr($hex, 2, 2));
            $b   = hexdec(substr($hex, 4, 2));

            return [
                'dot'  => $color,
                'bg'   => "rgba($r,$g,$b,0.15)",
                'text' => '#374151',
            ];
        }

        return static::$colorPresets['gray'];
    }

    protected function resolvePreview(): Renderable|string
    {
        $value = $this->toFormattedValue() ?? $this->toValue();

        if ($value === null || $value === '') {
            return '—';
        }

        $option = $this->coloredOptions[$value]
               ?? $this->coloredOptions[(string) $value]
               ?? null;

        if (! $option) {
            return e((string) $value);
        }

        $label     = is_array($option) ? ($option['label'] ?? (string) $value) : (string) $option;
        $colorSpec = is_array($option) ? ($option['color'] ?? 'gray') : 'gray';
        $styles    = $this->resolveColorStyles($colorSpec);

        return sprintf(
            '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;'
            . 'border-radius:20px;background-color:%s;font-size:13px;font-weight:500;'
            . 'color:%s;white-space:nowrap;">'
            . '<span style="width:8px;height:8px;border-radius:50%%;background-color:%s;flex-shrink:0;"></span>'
            . '%s</span>',
            $styles['bg'],
            $styles['text'],
            $styles['dot'],
            e($label)
        );
    }

    protected function resolveValue(): mixed
    {
        $value = parent::resolveValue();

        if (is_null($value) && ! is_null($this->defaultValue)) {
            return $this->defaultValue;
        }

        return $value;
    }

    protected function viewData(): array
    {
        $formattedOptions = [];

        foreach ($this->coloredOptions as $id => $option) {
            $label  = is_array($option) ? ($option['label'] ?? (string) $id) : (string) $option;
            $color  = is_array($option) ? ($option['color'] ?? 'gray') : 'gray';
            $styles = $this->resolveColorStyles($color);

            $formattedOptions[] = [
                'id'   => (string) $id,
                'name' => $label,
                'dot'  => $styles['dot'],
                'bg'   => $styles['bg'],
                'text' => $styles['text'],
            ];
        }

        return [
            'element'     => $this,
            'options'     => $formattedOptions,
            'selectedId'  => (string) ($this->toValue() ?? ''),
            'placeholder' => $this->placeholder,
            'rules'       => $this->customClientRules,
        ];
    }
}