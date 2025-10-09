<?php

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\UI\Fields\Checkbox;

class FeatureField extends Checkbox
{
    protected string $view = 'fields.feature-field';

    protected Closure|bool $isLocked = false;
    protected Closure|string $unlockUrl = '#';
    protected Closure|string $featureLabel = '';

    public function __construct(Closure|string|null $label = null, ?string $column = null, ?Closure $formatted = null)
    {
        parent::__construct($label, $column, $formatted);
        $this->featureLabel = $this->getLabel();
        $this->setLabel('');
    }

    public function locked(Closure|bool $condition): static
    {
        $this->isLocked = $condition;
        return $this;
    }

    public function getIsLocked(): bool
    {
        return is_callable($this->isLocked) ? call_user_func($this->isLocked) : $this->isLocked;
    }

    public function unlockUrl(Closure|string $url): static
    {
        $this->unlockUrl = $url;
        return $this;
    }

    public function getUnlockUrl(): string
    {
        return is_callable($this->unlockUrl) ? call_user_func($this->unlockUrl) : $this->unlockUrl;
    }


    public function getFeatureLabel(): string
    {
        return is_callable($this->featureLabel) ? call_user_func($this->featureLabel) : $this->featureLabel;
    }



    protected function viewData(): array
    {
        return [
            ...parent::viewData(),
            'field' => $this,
        ];
    }

}
