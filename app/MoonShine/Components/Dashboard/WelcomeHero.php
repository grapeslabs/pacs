<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class WelcomeHero extends MoonShineComponent
{
    protected string $view = 'components.dashboard.welcome-hero';

    public function __construct(
        public string $greeting = '',
        public string $name = '',
        public string $today = '',
        public ?string $streamsUrl = null,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'greeting' => $this->greeting,
            'name' => $this->name,
            'today' => $this->today,
            'streamsUrl' => $this->streamsUrl,
        ];
    }
}
