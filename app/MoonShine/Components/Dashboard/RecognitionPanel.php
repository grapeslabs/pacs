<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class RecognitionPanel extends MoonShineComponent
{
    protected string $view = 'components.dashboard.recognition-panel';

    public function __construct(
        public array $items = [],
        public int $camerasCount = 0,
        public int $total = 0,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'items' => $this->items,
            'camerasCount' => $this->camerasCount,
            'total' => $this->total,
        ];
    }
}
