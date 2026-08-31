<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

class UnsavedChangesModal extends MoonShineComponent
{
    protected string $view = 'components.unsaved-changes-modal';

    public static function make(...$arguments): static
    {
        return new static();
    }

    protected function viewData(): array
    {
        return [];
    }
}
