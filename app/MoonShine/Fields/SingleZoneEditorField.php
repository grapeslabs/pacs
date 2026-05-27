<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

final class SingleZoneEditorField extends Field
{
    protected string $view = 'fields.zone-editor-field';

    protected ?string $cancelUrl = null;

    protected ?string $saveUrl = null;

    protected string $saveText = 'Настройки сохранены';

    public function cancelUrl(string $url): self
    {
        $this->cancelUrl = $url;

        return $this;
    }

    public function saveUrl(string $url): self
    {
        $this->saveUrl = $url;

        return $this;
    }

    public function saveText(string $text): self
    {
        $this->saveText = $text;

        return $this;
    }

    public function getCancelUrl(): ?string
    {
        return $this->cancelUrl;
    }

    public function getSaveUrl(): ?string
    {
        return $this->saveUrl;
    }

    public function getSaveText(): string
    {
        return $this->saveText;
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
            'element' => $this,
            'item' => $this->getData()?->getOriginal()
        ];
    }
}
