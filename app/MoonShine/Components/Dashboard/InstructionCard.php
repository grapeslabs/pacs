<?php

namespace App\MoonShine\Components\Dashboard;

use MoonShine\UI\Components\MoonShineComponent;

final class InstructionCard extends MoonShineComponent
{
    protected string $view = 'components.dashboard.instruction-card';

    public function __construct(
        public string $title = 'Инструкция Пакс',
        public string $description = '',
        public string $btnText = 'Перейти к инструкции',
        public ?string $btnUrl = null,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'btnText' => $this->btnText,
            'btnUrl' => $this->btnUrl,
        ];
    }
}
