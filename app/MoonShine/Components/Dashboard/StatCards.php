<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class StatCards extends MoonShineComponent
{
    protected string $view = 'components.dashboard.stat-cards';

    public function __construct(
        public array $cards = [],
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return ['cards' => $this->cards];
    }
}
