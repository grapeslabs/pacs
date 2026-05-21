<?php

namespace App\MoonShine\Fields;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

class ZonePreviewField extends Field
{
    protected string $view = 'fields.zone-preview';

    protected string $setupUrl = '';

    public function setupUrl(string $url): self
    {
        $this->setupUrl = $url;

        return $this;
    }

    public function getSetupUrl(): string
    {
        return $this->setupUrl;
    }

    protected function prepareFill(array $raw = [], ?DataWrapperContract $casted = null): mixed
    {
        $value = data_get($raw, str_replace('->', '.', $this->getColumn()));

        if (!is_null($value) && $value !== false) {
            return $value;
        }

        return parent::prepareFill($raw, $casted);
    }

    public function viewData(): array
    {
        return [
            'element' => $this
        ];
    }
}
