<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class EventsPanel extends MoonShineComponent
{
    protected string $view = 'components.dashboard.events-panel';

    public function __construct(
        public array $events = [],
        public ?string $allUrl = null,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'events' => $this->events,
            'allUrl' => $this->allUrl,
        ];
    }
}
