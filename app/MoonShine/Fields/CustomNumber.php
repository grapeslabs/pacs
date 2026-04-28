<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Number;

class CustomNumber extends Number
{
    protected string $view = 'fields.custom-number';

    protected array $customClientRules = [];

    public function required(\Closure|bool|string|null $condition = null, string $message = 'Обязательное поле'): static
    {
        if (is_string($condition)) {
            $message = $condition;
            $condition = null;
        }

        $this->customClientRules[] = [
            'type' => 'required',
            'message' => $message
        ];

        return parent::required($condition);
    }

    public function minValue(float|int $val, string $message = 'Значение слишком мало'): static
    {
        $this->customClientRules[] = [
            'type' => 'minValue',
            'value' => $val,
            'message' => $message
        ];

        return $this;
    }

    public function maxValue(float|int $val, string $message = 'Значение слишком велико'): static
    {
        $this->customClientRules[] = [
            'type' => 'maxValue',
            'value' => $val,
            'message' => $message
        ];

        return $this;
    }

    public function step(float|int $val, string $message = 'Некорректный шаг значения'): static
    {
        $this->customClientRules[] = [
            'type' => 'step',
            'value' => $val,
            'message' => $message
        ];

        return parent::step($val);
    }

    public function integer(string $message = 'Должно быть целым числом'): static
    {
        $this->customClientRules[] = [
            'type' => 'integer',
            'message' => $message
        ];

        return $this;
    }

    public function positive(string $message = 'Число должно быть положительным'): static
    {
        $this->customClientRules[] = [
            'type' => 'positive',
            'message' => $message
        ];

        return $this;
    }

    public function unique(string $table, string $column, string $message = 'Данное значение уже занято'): static
    {
        $this->customClientRules[] = [
            'type' => 'unique',
            'table' => $table,
            'column' => $column,
            'message' => $message
        ];

        return $this;
    }

    public function exists(string $table, string $column, string $message = 'Значение не найдено в системе'): static
    {
        $this->customClientRules[] = [
            'type' => 'exists',
            'table' => $table,
            'column' => $column,
            'message' => $message
        ];

        return $this;
    }

    public function getCustomClientRules(): array
    {
        return $this->customClientRules;
    }

    protected function viewData(): array
    {
        return [...parent::viewData(), 'element'=>$this];
    }
}
