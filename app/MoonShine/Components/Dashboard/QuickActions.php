<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class QuickActions extends MoonShineComponent
{
    protected string $view = 'components.dashboard.quick-actions';

    public function __construct(
        public array $actions = [],
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return ['actions' => $this->actions];
    }
}
