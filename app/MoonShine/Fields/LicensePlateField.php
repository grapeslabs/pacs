<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

class LicensePlateField extends Field
{
    protected string $view = 'fields.license-plate-field';

    private const PLATE_LETTERS = 'АВЕКМНОРСТУХABEKMHOPCTYX';

    protected function parse(string $plate): array
    {
        $normalized = mb_strtoupper(preg_replace('/\s+/u', '', $plate) ?? '');
        $letters = self::PLATE_LETTERS;

        if (preg_match('/^([' . $letters . '])(\d{3})([' . $letters . ']{2})(\d{2,3})$/u', $normalized, $m)) {
            return [
                'main' => $m[1] . ' ' . $m[2] . ' ' . $m[3],
                'region' => $m[4],
            ];
        }

        return [
            'main' => trim($plate),
            'region' => null,
        ];
    }

    protected function buildData(): array
    {
        $plate = (string) ($this->toValue() ?? '');

        return [
            'plate' => $plate,
            ...$this->parse($plate),
        ];
    }

    protected function resolvePreview(): Renderable|string
    {
        if (trim((string) ($this->toValue() ?? '')) === '') {
            return '—';
        }

        return view($this->view, $this->buildData());
    }

    protected function viewData(): array
    {
        return $this->buildData();
    }
}
