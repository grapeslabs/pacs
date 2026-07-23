<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use App\Services\MenuService;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\UI\Components\MoonShineComponent;

final class CustomSidebar extends MoonShineComponent
{
    protected string $view = 'components.layout.custom-sidebar';

    protected function viewData(): array
    {
        $menu = app(MenuService::class)->visibleMenu(MoonShineAuth::getGuard()->user());

        return [
            'home' => $menu['home'],
            'sections' => $menu['sections'],
            'version' => config('version.number'),
        ];
    }
}
