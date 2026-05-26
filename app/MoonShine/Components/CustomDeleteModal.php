<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

class CustomDeleteModal extends MoonShineComponent
{
    protected string $view = 'components.custom-delete-modal';

    public static function make(...$arguments): static
    {
        return new static();
    }

    protected function viewData(): array
    {
        return [];
    }
}