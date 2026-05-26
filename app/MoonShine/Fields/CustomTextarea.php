<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Textarea;

class CustomTextarea extends Textarea
{
    protected string $view = 'fields.custom-textarea';

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

    public function min(int $length, string $message = 'Минимум символов'): static
    {
        $this->customClientRules[] = [
            'type' => 'min',
            'value' => $length,
            'message' => $message
        ];

        return $this;
    }

    public function max(int $length, string $message = 'Максимум символов'): static
    {
        $this->customClientRules[] = [
            'type' => 'max',
            'value' => $length,
            'message' => $message
        ];

        return $this;
    }

    public function pattern(string $regex, string $message = 'Неверный формат'): static
    {
        $jsRegex = preg_replace('/^\/(.*)\/[a-zA-Z]*$/', '$1', $regex);

        $this->customClientRules[] = [
            'type' => 'pattern',
            'value' => $jsRegex,
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
        return [...parent::viewData(), 'element' => $this];
    }
}
