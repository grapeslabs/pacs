<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class SearchBox extends MoonShineComponent
{
    protected string $view = 'components.layout.search-box';

    protected function viewData(): array
    {
        return [
            'searchUrl' => route('system.search'),
        ];
    }
}
