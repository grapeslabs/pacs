<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Components\CustomDeleteModal;
use App\MoonShine\Components\CustomSidebar;
use App\MoonShine\Components\HeaderBar;
use App\MoonShine\Components\SafeModal;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Laravel\Components\Fragment;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\UI\Components\Breadcrumbs;
use MoonShine\UI\Components\Layout\Body;
use MoonShine\UI\Components\Layout\Content;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Favicon;
use MoonShine\UI\Components\Layout\Flash;
use MoonShine\UI\Components\Layout\Html;
use MoonShine\UI\Components\Layout\Layout;
use MoonShine\UI\Components\Layout\Sidebar;
use MoonShine\UI\Components\Layout\Wrapper;
use MoonShine\UI\Components\Link;

final class MoonShineLayout extends AppLayout
{
    protected string $safeModalName = 'universal-safe-modal';

    public function boot(): void
    {
        parent::boot();
        $this->app->singleton(MoonShineLayoutContract::class, function () {
            $layout = parent::resolveLayout();
            $layout->forceLightTheme();
            return $layout;
        });
    }
    protected function assets(): array
    {
        return [
            ...parent::assets(),
            Css::make('/css/moonshine-custom.css'),
            Js::make('/js/force-light.js'),
        ];
    }

    protected function isAlwaysDark(): bool
    {
        return false;
    }

    protected function getSidebarComponent(): Sidebar
    {
        return Sidebar::make([
            CustomSidebar::make(),
        ])
            ->customAttributes([
                'x-effect' => '$el.classList.toggle("_is-opened", asideMenuOpen)',
            ]);
    }

    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);
        $colorManager->primary('transparent');
        $colorManager->secondary('transparent');
        $colorManager->errorBg('transparent');
    }

    protected function withTitle(): bool
    {
        return false;
    }

    protected function getFooterCopyright(): string
    {
        return '© 2007-2026 Grapes labs';
    }

    protected function getFooterMenu(): array
    {
        return [
            Link::make('https://grapeslabs.ru', 'О нас')->blank(),
        ];
    }

    protected function getFaviconComponent(): Favicon
    {
        return parent::getFaviconComponent()->customAssets([
            'apple-touch' => asset('images/logo_small.svg'),
            '32' => asset('images/logo_small.svg'),
            '16' => asset('images/logo_small.svg'),
            'safari-pinned-tab' => asset('images/logo_small.svg'),
            'web-manifest' => asset('images/logo_small.svg'),
        ]);
    }

    public function build(): Layout
    {
        return Layout::make([
            Html::make([
                $this->getHeadComponent(),
                Body::make([
                    CustomDeleteModal::make(),
                    Div::make()->customView('components.error-notification'),
                    Div::make()->customView('components.detail-sidebar'),
                    SafeModal::make(title: fn () => 'Редактирование', async: true)->name($this->safeModalName),
                    HeaderBar::make(
                        $this->getHomeUrl(),
                        (string) Breadcrumbs::make($this->getPage()->getBreadcrumbs())
                            ->prepend($this->getHomeUrl(), icon: 'home')
                            ->render(),
                    ),
                    Wrapper::make([
                        $this->getSidebarComponent(),
                        Div::make([
                            Fragment::make([
                                Flash::make(),
                                Content::make($this->getContentComponents()),
                                $this->getFooterComponent(),
                            ])->class('layout-page')->name(self::CONTENT_FRAGMENT_NAME),
                        ])->class('flex grow overflow-auto')->customAttributes(['id' => self::CONTENT_ID]),
                    ]),
                ])->customAttributes(['class' => 'pacs-shell']),
            ])
                ->customAttributes([
                    'lang' => $this->getHeadLang(),
                ])
                ->withAlpineJs()
                ->withThemes($this->isAlwaysDark()),
        ]);
    }
}
