<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Text;

class CustomText extends Text
{
    protected string $view = 'fields.custom-text';

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

    public function min(int $length, string $message = 'Минимум символов'): static
    {
        $this->customClientRules[] =[
            'type' => 'min',
            'value' => $length,
            'message' => $message
        ];

        return $this;
    }

    public function max(int $length, string $message = 'Максимум символов'): static
    {
        $this->customClientRules[] =[
            'type' => 'max',
            'value' => $length,
            'message' => $message
        ];

        return $this;
    }

    public function pattern(string $regex, string $message = 'Неверный формат'): static
    {
        $jsRegex = preg_replace('/^\/(.*)\/[a-zA-Z]*$/', '$1', $regex);

        $this->customClientRules[] =[
            'type' => 'pattern',
            'value' => $jsRegex,
            'message' => $message
        ];

        return $this;
    }

    public function nameFormat(string $message = 'Имя должно содержать буквы'): static
    {
        $this->customClientRules[] =[
            'type' => 'nameFormat',
            'message' => $message
        ];

        return $this;
    }

    public function email(string $message = 'Введите корректный email'): static
    {
        $this->customClientRules[] =[
            'type' => 'email',
            'message' => $message
        ];

        return $this;
    }

    public function url(string $message = 'Введите корректный URL'): static
    {
        $this->customClientRules[] =[
            'type' => 'url',
            'message' => $message
        ];

        return $this;
    }

    public function ipv4(string $message = 'Неверный IPv4 адрес'): static
    {
        $this->customClientRules[] =[
            'type' => 'ipv4',
            'message' => $message
        ];

        return $this;
    }

    public function ipv6(string $message = 'Неверный IPv6 адрес'): static
    {
        $this->customClientRules[] = [
            'type' => 'ipv6',
            'message' => $message
        ];

        return $this;
    }

    public function alpha(string $message = 'Допустимы только буквы'): static
    {
        $this->customClientRules[] =[
            'type' => 'alpha',
            'message' => $message
        ];

        return $this;
    }

    public function alphaNum(string $message = 'Только буквы и цифры'): static
    {
        $this->customClientRules[] =[
            'type' => 'alphaNum',
            'message' => $message
        ];

        return $this;
    }

    public function phone(string $message = 'Неверный формат телефона'): static
    {
        $this->customClientRules[] =[
            'type' => 'phone',
            'message' => $message
        ];

        return $this;
    }

    public function unique(string $table, string $column, string $message = 'Данное значение уже занято'): static
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
        return $this->customClientRules;
    }

    protected function viewData(): array
    {
        return [...parent::viewData(), 'element'=>$this];
    }
}
