<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class EventLogComponent extends MoonShineComponent
{
    protected string $view = 'components.event-log-component';

    protected iterable $items = [];
    public function __construct(iterable $items = [])
    {
        parent::__construct();

        $this->items = $items;
    }

    public function items(iterable $items): self
    {
        $this->items = $items;

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'items' => $this->items,
        ];
    }
}
