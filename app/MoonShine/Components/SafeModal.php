<?php
namespace App\MoonShine\Components;

use Closure;
use MoonShine\UI\Components\MoonShineComponent;

class SafeModal extends MoonShineComponent
{
    protected string $view = 'components.safe-modal';

    public function __construct(
        public string|Closure|null $title = '',
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
            'name' => $this->getName() ?? 'safe-modal',
            'title' => $this->title,
            'async' => $this->async,
        ];
    }

}
