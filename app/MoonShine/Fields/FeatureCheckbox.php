<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

class FeatureCheckbox extends Field
{
    protected string $view = 'fields.feature-checkbox';

    protected function prepareFill(array $raw = [], ?DataWrapperContract $casted = null): mixed
    {
        $value = data_get($raw, str_replace('->', '.', $this->getColumn()));
        if (!is_null($value) && $value !== false) {
            return $value;
        }
        return parent::prepareFill($raw, $casted);
    }

    protected function viewData(): array
    {
        return [
            'element' => $this,
        ];
    }

    protected function resolveOnApply(): ?Closure
    {
        return function ($item) {
            $value = $this->getRequestValue();
            $item->{$this->getColumn()} = ($value === '1' || $value === true || $value === 1) ? 1 : 0;

            return $item;
        };
    }
}
