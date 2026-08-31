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
        protected ?Closure $editUrlResolver = null,
        protected ?Closure $deleteUrlResolver = null
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

    public function deleteUrlResolver(Closure $resolver): self
    {
        $this->deleteUrlResolver = $resolver;

        return $this;
    }

    protected function viewData(): array
    {
        return[
            'items' => $this->items,
            'editUrlResolver' => $this->editUrlResolver,
            'deleteUrlResolver' => $this->deleteUrlResolver,
            'isDriveLimitStopped' => Cache::get('drive_limit_stoped', false),
        ];
    }
}
