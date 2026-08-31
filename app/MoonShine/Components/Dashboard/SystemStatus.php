<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class SystemStatus extends MoonShineComponent
{
    protected string $view = 'components.dashboard.system-status';

    public function __construct(
        public array $system = [],
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return ['system' => $this->system];
    }
}
