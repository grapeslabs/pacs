<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

class FeatureSlider extends Field
{
    protected string $view = 'fields.feature-slider';

    protected int $min = 0;
    protected int $max = 100;
    protected int $step = 1;
    protected int $default;

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

    public function default(int $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function getValue(bool $withOld = true): mixed
    {
        return parent::getValue()??$this->default;
    }

    public function min(int $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function step(int $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getStep(): int
    {
        return $this->step;
    }
}
