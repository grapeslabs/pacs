<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use App\Services\MenuService;
use MoonShine\UI\Components\MoonShineComponent;

final class HeaderBar extends MoonShineComponent
{
    protected string $view = 'components.layout.header-bar';

    /** @param array<string, string> $breadcrumbs url => label */
    public function __construct(
        protected string $homeUrl = '',
        protected array $breadcrumbs = [],
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        $crumbs = [];

        foreach ($this->breadcrumbs as $url => $label) {
            $label = trim(strip_tags((string) $label));

            if ($label === '') {
                continue;
            }

            $crumbs[] = ['url' => (string) $url, 'label' => $label];
        }

        // Раздел меню (напр. «Данные СКУД») перед названием страницы, кроме «Главной»
        $section = rescue(
            fn (): ?string => app(MenuService::class)->sectionLabelFor((string) request()->url()),
            null,
            report: false,
        );

        return [
            'homeUrl' => $this->homeUrl,
            'logoUrl' => asset('icons/logo.svg'),
            'crumbs' => $crumbs,
            'crumbSection' => $section,
        ];
    }
}
