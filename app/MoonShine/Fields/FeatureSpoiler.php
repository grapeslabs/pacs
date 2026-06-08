<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Traits\WithFields;

class FeatureSpoiler extends Field
{
    use WithFields;

    protected string $view = 'fields.feature-spoiler';

    public function nested(array $fields): static
    {
        $this->fields($fields);

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'element' => $this,
        ];
    }

    protected function prepareFill(array $raw = [], ?DataWrapperContract $casted = null): mixed
    {
        $value = data_get($raw, str_replace('->', '.', $this->getColumn()));
        if (!is_null($value) && $value !== false) {
            return $value;
        }
        return parent::prepareFill($raw, $casted);
    }

    protected function resolveFill(array $raw = [], ?DataWrapperContract $casted = null, int $index = 0): static
    {
        parent::resolveFill($raw, $casted, $index);

        $this->getFields()->fill($raw, $casted, $index);

        return $this;
    }

    public function hasShowWhen(): bool
    {
        if (parent::hasShowWhen()) {
            return true;
        }

        foreach ($this->getFields()->onlyFields() as $field) {
            if ($field->hasShowWhen()) {
                return true;
            }
        }
        return false;
    }

    public function getShowWhenCondition(): array
    {
        $conditions = parent::getShowWhenCondition();

        foreach ($this->getFields()->onlyFields() as $field) {
            if ($field->hasShowWhen()) {
                foreach ($field->getShowWhenCondition() as $condition) {
                    $conditions[] = $condition;
                }
            }
        }

        return $conditions;
    }

    protected function resolveOnApply(): ?Closure
    {
        return function ($item) {
            $value = $this->getRequestValue();
            $item->{$this->getColumn()} = ($value === '1' || $value === true || $value === 1) ? 1 : 0;

            $this->getFields()->onlyFields()->each(function (FieldContract $field) use ($item) {
                $field->apply(
                    static function (mixed $item) use ($field): mixed {
                        if ($field->getRequestValue() !== false) {
                            data_set($item, $field->getColumn(), $field->getRequestValue());
                        }
                        return $item;
                    },
                    $item
                );
            });

            return $item;
        };
    }
}
