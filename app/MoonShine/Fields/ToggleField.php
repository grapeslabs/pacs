<?php

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\UI\Fields\Checkbox;

class ToggleField extends Checkbox
{
    protected string $view = 'fields.toggle-field';

    protected Closure|string $toggleLabel = '';
    protected Closure|string $toggleSubtitle = '';

    public function __construct(Closure|string|null $label = null, ?string $column = null, ?Closure $formatted = null)
    {
        parent::__construct($label, $column, $formatted);
        $this->toggleLabel = $this->getLabel();
        $this->setLabel('');
    }

    public function subtitle(Closure|string $subtitle): static
    {
        $this->toggleSubtitle = $subtitle;
        return $this;
    }

    public function getToggleLabel(): string
    {
        return is_callable($this->toggleLabel) ? call_user_func($this->toggleLabel) : $this->toggleLabel;
    }

    public function getToggleSubtitle(): string
    {
        return is_callable($this->toggleSubtitle) ? call_user_func($this->toggleSubtitle) : $this->toggleSubtitle;
    }

    protected function viewData(): array
    {
        return [
            ...parent::viewData(),
            'field' => $this,
        ];
    }
}
