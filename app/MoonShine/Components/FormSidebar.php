<?php
namespace App\MoonShine\Components;

use Closure;
use MoonShine\UI\Components\MoonShineComponent;

class FormSidebar extends MoonShineComponent
{
    protected string $view = 'components.form-sidebar';

    public function __construct(
        public string|Closure|null $title = '',
        public string|Closure|null $subtitle = '',
        public bool $async = false,
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
            'name' => $this->getName() ?? 'form-sidebar',
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'async' => $this->async,
        ];
    }
}
