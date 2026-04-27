<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;
use Illuminate\Support\Facades\Cache;
use Closure;

final class StreamGridComponent extends MoonShineComponent
{
    protected string $view = 'components.stream-grid-component';

    public function __construct(
        protected iterable $items =[],
        protected ?Closure $editUrlResolver = null
    ) {
        parent::__construct();
    }

    public function items(iterable $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function editUrlResolver(Closure $resolver): self
    {
        $this->editUrlResolver = $resolver;

        return $this;
    }

    protected function viewData(): array
    {
        return[
            'items' => $this->items,
            'editUrlResolver' => $this->editUrlResolver,
            'isDriveLimitStopped' => Cache::get('drive_limit_stoped', false),
        ];
    }
}
