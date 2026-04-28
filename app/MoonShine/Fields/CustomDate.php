<?php

namespace App\MoonShine\Fields;

use Carbon\Carbon;
use MoonShine\UI\Fields\Date;

class CustomDate extends Date
{
    protected string $view = 'fields.custom-date';

    protected array $customClientRules =[];

    public function required(\Closure|bool|string|null $condition = null, string $message = 'Обязательное поле'): static
    {
        if (is_string($condition)) {
            $message = $condition;
            $condition = null;
        }

        $this->customClientRules[] =[
            'type' => 'required',
            'message' => $message
        ];

        return parent::required($condition);
    }

    public function before(string $date, string $message = 'Дата не может быть позже указанной'): static
    {
        $this->customClientRules[] =[
            'type' => 'before',
            'value' => Carbon::parse($date)->toIso8601String(),
            'message' => $message
        ];

        return $this;
    }

    public function after(string $date, string $message = 'Дата не может быть раньше указанной'): static
    {
        $this->customClientRules[] =[
            'type' => 'after',
            'value' => Carbon::parse($date)->toIso8601String(),
            'message' => $message
        ];

        return $this;
    }

    public function beforeField(string $fieldName, string $message = 'Дата должна быть раньше'): static
    {
        $this->customClientRules[] =[
            'type' => 'beforeField',
            'field' => $fieldName,
            'message' => $message
        ];

        return $this;
    }

    public function afterField(string $fieldName, string $message = 'Дата должна быть позже'): static
    {
        $this->customClientRules[] =[
            'type' => 'afterField',
            'field' => $fieldName,
            'message' => $message
        ];

        return $this;
    }

    public function sameField(string $fieldName, string $message = 'Даты должны совпадать'): static
    {
        $this->customClientRules[] =[
            'type' => 'sameField',
            'field' => $fieldName,
            'message' => $message
        ];

        return $this;
    }

    public function unique(string $table, string $column, string $message = 'Данная дата уже занята'): static
    {
        $this->customClientRules[] =[
            'type' => 'unique',
            'table' => $table,
            'column' => $column,
            'message' => $message
        ];

        return $this;
    }

    public function exists(string $table, string $column, string $message = 'Значение не найдено в системе'): static
    {
        $this->customClientRules[] =[
            'type' => 'exists',
            'table' => $table,
            'column' => $column,
            'message' => $message
        ];

        return $this;
    }

    public function getCustomClientRules(): array
    {
        return array_values($this->customClientRules);
    }

    protected function viewData(): array
    {
        return[...parent::viewData(), 'element' => $this];
    }
}
