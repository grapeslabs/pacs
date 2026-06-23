<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class WelcomeHero extends MoonShineComponent
{
    protected string $view = 'components.dashboard.welcome-hero';

    protected string $userName;

    public function __construct(
        public string $greeting = '',
        string $name = '',
        public string $today = '',
        public ?string $streamsUrl = null,
    ) {
        parent::__construct();
        $this->userName = $name;
    }

    protected function viewData(): array
    {
        return [
            'greeting' => $this->greeting,
            'userName' => $this->userName,
            'today' => $this->today,
            'streamsUrl' => $this->streamsUrl,
        ];
    }
}
