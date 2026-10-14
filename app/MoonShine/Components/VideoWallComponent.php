<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class VideoWallComponent extends MoonShineComponent
{
    protected string $view = 'components.video-wall';

    public function __construct(
        protected array $walls = [],
        protected array $streams = [],
        protected array $state = []
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'walls' => $this->walls,
            'streams' => $this->streams,
            'state' => $this->state,
        ];
    }
}
