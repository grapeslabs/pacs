<?php

namespace App\MoonShine\Components;

use Closure;
use MoonShine\UI\Components\MoonShineComponent;

class FilterSidebar extends MoonShineComponent
{
    protected string $view = 'components.filter-sidebar';

    public function __construct(
        public string|Closure|null $title = 'Фильтры',
        public string|Closure|null $subtitle = '',
        protected mixed $form = null,
        public int $count = 0,
    ) {
        parent::__construct();
    }

    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }

    protected function viewData(): array
    {
        return [
            'name' => $this->getName() ?? 'filter-sidebar',
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'form' => $this->form,
            'count' => $this->count,
        ];
    }
}
