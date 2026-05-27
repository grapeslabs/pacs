<?php

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\UI\Fields\Field;

class ArchiveDuration extends Field
{
    protected string $view = 'fields.archive-duration';

    protected function resolveValue(): mixed
    {
        $raw = parent::resolveValue();

        if (is_array($raw)) {
            return $raw;
        }

        $raw = (int) $raw;
        $unit = 24;
        $value = 0;

        if ($raw > 0) {
            if ($raw % 8760 === 0) {
                $value = $raw / 8760;
                $unit = 8760;
            } elseif ($raw % 720 === 0) {
                $value = $raw / 720;
                $unit = 720;
            } elseif ($raw % 168 === 0) {
                $value = $raw / 168;
                $unit = 168;
            } elseif ($raw % 24 === 0) {
                $value = $raw / 24;
                $unit = 24;
            } else {
                $value = $raw;
                $unit = 1;
            }
        }

        return [
            'value' => $value,
            'unit' => $unit,
        ];
    }

    public function apply(Closure $default, mixed $data): mixed
    {
        $requestValue = $this->getRequestValue();

        if (is_array($requestValue)) {
            $val = (int) ($requestValue['value'] ?? 0);
            $unit = (int) ($requestValue['unit'] ?? 24);

            $data->{$this->getColumn()} = $val * $unit;

            return $data;
        }

        return parent::apply($default, $data);
    }

    protected function viewData(): array
    {
        return [...parent::viewData(), 'element' => $this];
    }
}
