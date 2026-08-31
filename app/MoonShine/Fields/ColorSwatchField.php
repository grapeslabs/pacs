<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

class ColorSwatchField extends Field
{
    protected string $view = 'fields.color-swatch-field';
    private const COLORS = [
        'чёрный' => '#1b1b1b', 'черный' => '#1b1b1b',
        'белый' => '#F5F5F5',
        'серебристый' => '#C7CAD1', 'серебряный' => '#C7CAD1',
        'серый' => '#8A8F98',
        'синий' => '#2F5BD6', 'голубой' => '#38BDF8',
        'красный' => '#D6342C',
        'зелёный' => '#3FA857', 'зеленый' => '#3FA857',
        'жёлтый' => '#EAB308', 'желтый' => '#EAB308',
        'оранжевый' => '#F97316',
        'коричневый' => '#8B5A2B',
        'бордовый' => '#7B1E28',
        'фиолетовый' => '#7C3AED',
        'бежевый' => '#E3D5B8',
        'золотистый' => '#C9A227', 'золотой' => '#C9A227',
    ];

    public static function hexFor(?string $name): string
    {
        $key = mb_strtolower(trim((string) $name));

        return self::COLORS[$key] ?? '#B9BECB';
    }

    public static function swatchHtml(?string $name): string
    {
        if (blank($name)) {
            return '—';
        }

        return '<span class="ds-swatch"><span class="ds-swatch__box" style="background:'
            . e(self::hexFor($name)) . ';"></span>' . e($name) . '</span>';
    }

    protected function resolvePreview(): Renderable|string
    {
        $name = $this->toFormattedValue() ?? $this->toValue();

        if (blank($name)) {
            return '—';
        }

        return view($this->view, [
            'name' => (string) $name,
            'hex' => self::hexFor((string) $name),
        ]);
    }

    protected function viewData(): array
    {
        $name = (string) ($this->toFormattedValue() ?? $this->toValue() ?? '');

        return ['name' => $name, 'hex' => self::hexFor($name)];
    }
}
