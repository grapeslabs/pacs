<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Text;

final class DadataOrganizationField extends Text
{
    protected string $view = 'fields.dadata-organization';

    public function isNowOnForm(): bool 
    {
        return false;
    }

    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);
        
        $this->customAttributes([
            'data-dadata-field' => 'true',
            'autocomplete' => 'off',
        ]);
    }

    public function save(mixed $value): mixed
    {
        return null;
    }

    public function resolveFill(array $raw = [], mixed $casted = null, int $index = 0): static
    {
        $this->setValue('');
        return $this;
    }
}