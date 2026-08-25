<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class LogViewer extends MoonShineComponent
{
    protected string $view = 'components.log-viewer';

    public function __construct(
        protected array $sources = [],
        protected array $levels = [],
        protected array $entries = [],
        protected array $state = [],
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'sources' => $this->sources,
            'levels' => $this->levels,
            'entries' => $this->entries,
            'state' => $this->state,
        ];
    }
}
