<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class HeaderBar extends MoonShineComponent
{
    protected string $view = 'components.layout.header-bar';

    public function __construct(
        protected string $homeUrl = '',
        protected string $breadcrumbs = '',
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'homeUrl' => $this->homeUrl,
            'logoUrl' => asset('images/logo.svg'),
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
