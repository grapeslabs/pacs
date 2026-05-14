<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Password;

class CustomPassword extends Password
{
    protected string $view = 'fields.custom-password';

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

    public function min(int $length, string $message = 'Слишком короткий пароль'): static
    {
        $this->customClientRules[] =[
            'type' => 'min',
            'value' => $length,
            'message' => $message
        ];

        return $this;
    }

    public function max(int $length, string $message = 'Слишком длинный пароль'): static
    {
        $this->customClientRules[] =[
            'type' => 'max',
            'value' => $length,
            'message' => $message
        ];

        return $this;
    }

    public function hasUpper(string $message = 'Нужна заглавная буква'): static
    {
        $this->customClientRules[] =[
            'type' => 'hasUpper',
            'message' => $message
        ];

        return $this;
    }

    public function hasLower(string $message = 'Нужна строчная буква'): static
    {
        $this->customClientRules[] =[
            'type' => 'hasLower',
            'message' => $message
        ];

        return $this;
    }

    public function hasDigit(string $message = 'Нужна хотя бы одна цифра'): static
    {
        $this->customClientRules[] =[
            'type' => 'hasDigit',
            'message' => $message
        ];

        return $this;
    }

    public function hasSpecial(string $message = 'Нужен спецсимвол'): static
    {
        $this->customClientRules[] =[
            'type' => 'hasSpecial',
            'message' => $message
        ];

        return $this;
    }

    public function confirm(string $fieldName, string $message = 'Пароли не совпадают'): static
    {
        $this->customClientRules[] =[
            'type' => 'confirm',
            'field' => $fieldName,
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
