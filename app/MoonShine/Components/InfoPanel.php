<?php
namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class InfoPanel extends MoonShineComponent
{
    protected string $view = 'components.info-panel';

    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $btnText = null,
        public ?string $btnUrl = null,
    ) {
        parent::__construct();
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function icon(string $iconAssetPath): self
    {
        $this->icon = $iconAssetPath;
        return $this;
    }

    public function btnText(string $btnText): self
    {
        $this->btnText = $btnText;
        return $this;
    }

    public function btnUrl(string $btnUrl): self
    {
        $this->btnUrl = $btnUrl;
        return $this;
    }

    protected function viewData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'btnText' => $this->btnText,
            'btnUrl' => $this->btnUrl,
        ];
    }
}
