<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Components\SafeModal;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Resources\EventReportResource;
use App\MoonShine\Resources\PeopleReportResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\UnknownReportResource;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Laravel\Components\Fragment;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Laravel\Components\Layout\{Locales, Notifications, Profile, Search};
use MoonShine\UI\Components\{Breadcrumbs,
    Components,
    Layout\Flash,
    Layout\Div,
    Layout\Body,
    Layout\Burger,
    Layout\Content,
    Layout\Footer,
    Layout\Head,
    Layout\Favicon,
    Layout\Assets,
    Layout\Meta,
    Layout\Header,
    Layout\Html,
    Layout\Layout,
    Layout\Logo,
    Layout\Menu,
    Layout\Sidebar,
    Layout\Wrapper,
    Link,
    When};
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use App\MoonShine\Resources\EquipmentResource;
use App\MoonShine\Resources\VideoStreamResource;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\OrganizationResource;
use App\MoonShine\Resources\TagResource;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\GuestResource;
use App\MoonShine\Resources\TerminalResource;
use App\MoonShine\Resources\BarrierResource;
use App\MoonShine\Resources\ControllerResource;
use App\MoonShine\Resources\ReferenceResource;
use App\MoonShine\Resources\SettingResource;
use App\MoonShine\Resources\CarBrandResource;
use App\MoonShine\Resources\CarColorResource;
use App\MoonShine\Resources\PersonPhotoResource;
use App\MoonShine\Resources\BotResource;
use MoonShine\Laravel\Enums\Ability;
use App\MoonShine\Resources\ApiKeyResource;
use App\MoonShine\Resources\SkudEventResource;
use App\MoonShine\Resources\BarrierEventResource;

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
            Js::make('/js/jquery-3.7.1.min.js'),
            Js::make('/js/force-light.js'),
            Js::make('/js/photo-field.js'),
        ];
    }

    protected function isAlwaysDark(): bool
    {
        return false;
    }

    protected function getSidebarComponent(): Sidebar
    {
        return Sidebar::make([
            Div::make([
                Div::make([
                    Burger::make(),
                ])->class('menu-heading-burger'),
            ])->class('menu-heading-actions'),

            Div::make([
                ...$this->sidebarSlot(),
                Menu::make(),
                When::make(
                    fn (): bool => $this->isProfileEnabled(),
                    fn (): array => [
                        $this->getProfileComponent(sidebar: true),
                    ],
                ),
            ])->customAttributes([
                'class' => 'menu',
            ]),
        ])
            ->customAttributes([
                ':class' => "asideMenuOpen ? '_is-opened' : ''"
            ])
            ->collapsed();
    }

    protected function menu(): array
    {
        return [
            MenuItem::make('Главная', Dashboard::class)->icon('home'),
            MenuGroup::make('Управление', [
                MenuItem::make('Пользователи', MoonShineUserResource::class)
                    ->icon('user-group')
                    ->canSee(fn () => auth()->user()->hasPermission(MoonShineUserResource::class, Ability::VIEW_ANY) or auth() -> user()->id==1),
                MenuItem::make('Роли', MoonShineUserRoleResource::class)
                    ->icon('rectangle-group')
                    ->canSee(fn () => auth()->user()->hasPermission(MoonShineUserRoleResource::class, Ability::VIEW_ANY)),
//                MenuItem::make('Ключи API', ApiKeyResource::class)
//                    ->icon(file_get_contents(public_path('icons/menu-apikeys.svg')), true)
//                    ->canSee(fn () => auth()->user()->hasPermission(ApiKeyResource::class, Ability::VIEW)),
                MenuItem::make('Настройки', SettingsPage::class)
                    ->icon('cog-8-tooth')
                    ->canSee(fn () => auth()->user()->hasPermission(SettingsPage::class, Ability::VIEW)),
            ])->icon(file_get_contents(public_path('icons/menu-settings.svg')),true),

            MenuItem::make('Видеопотоки', VideoStreamResource::class)
                ->icon(file_get_contents(public_path('icons/menu-video.svg')),true)
                ->canSee(fn () => auth()->user()->hasPermission(VideoStreamResource::class, Ability::VIEW_ANY)),

            MenuGroup::make('Данные СКУД', [
                MenuItem::make('Персоны', PersonResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(PersonResource::class, Ability::VIEW_ANY)),
                MenuItem::make('Организации', OrganizationResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(OrganizationResource::class, Ability::VIEW_ANY)),
                MenuItem::make('Ключи', KeyResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(KeyResource::class, Ability::VIEW_ANY)),
//                MenuItem::make('Автомобили', CarResource::class)
//                    ->canSee(fn () => auth()->user()->hasPermission(CarResource::class, Ability::VIEW_ANY)),
//                MenuItem::make('Гости', GuestResource::class)
//                    ->canSee(fn () => auth()->user()->hasPermission(GuestResource::class, Ability::VIEW_ANY)),
            ])->icon(file_get_contents(public_path('icons/menu-skud.svg')),true),

            MenuItem::make('Боты', BotResource::class)
                ->icon(file_get_contents(public_path('icons/menu-bot.svg')), true)
                ->canSee(fn () => auth()->user()->hasPermission(BotResource::class, Ability::VIEW_ANY)),
            MenuItem::make('Триггеры', TriggerResource::class)
                ->icon(file_get_contents(public_path('icons/menu-trigger.svg')), true)
                ->canSee(fn () => auth()->user()->hasPermission(TriggerResource::class, Ability::VIEW_ANY)),

            MenuGroup::make('Отчеты', [
                MenuItem::make('Отчеты СКУД', SkudEventResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(SkudEventResource::class, Ability::VIEW_ANY)),
                MenuItem::make('Отчеты шлагбаум', BarrierEventResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(BarrierEventResource::class, Ability::VIEW_ANY)),
                 MenuItem::make('Отчеты по событиям', EventReportResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(EventReportResource::class, Ability::VIEW_ANY)),
                MenuItem::make('Отчеты по персонам', PeopleReportResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(PeopleReportResource::class, Ability::VIEW_ANY)),
                MenuItem::make('Отчеты по неизвестным', UnknownReportResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(UnknownReportResource::class, Ability::VIEW_ANY)),
            ])->icon(file_get_contents(public_path('icons/menu-reports.svg')), true),

            MenuGroup::make('Оборудование', [
                MenuItem::make('Терминалы доступа', TerminalResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(TerminalResource::class, Ability::VIEW_ANY)),
//                MenuItem::make('Шлагбаумы', BarrierResource::class)
//                    ->canSee(fn () => auth()->user()->isHavePermission(BarrierResource::class, Ability::VIEW_ANY)),
                MenuItem::make('Контроллеры СКУД', ControllerResource::class)
                    ->canSee(fn () => auth()->user()->hasPermission(ControllerResource::class, Ability::VIEW_ANY)),
            ])->icon(file_get_contents(public_path('icons/menu-equipments.svg')),true),

//            MenuGroup::make('Справочники', [
//                MenuItem::make('Марки автомобилей', CarBrandResource::class)
//                    ->canSee(fn () => auth()->user()->hasPermission(CarBrandResource::class, Ability::VIEW_ANY)),
//                MenuItem::make('Цвета автомобилей', CarColorResource::class)
//                    ->canSee(fn () => auth()->user()->hasPermission(CarColorResource::class, Ability::VIEW_ANY)),
//            ])->icon(file_get_contents(public_path('icons/menu-references.svg')), true),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
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

    protected function getHeaderComponent(): Header
    {
        return Header::make([
            Burger::make(),
            Breadcrumbs::make($this->getPage()->getBreadcrumbs())->prepend($this->getHomeUrl(), icon: 'home'),
            Div::make()->customView('components.error-notification'),
            '<img src="/images/logo.svg" style="width: 10vh; height: 7vh; object-fit: contain;">',
            SafeModal::make(
                title: fn() => 'Редактирование',
                async: true
            )->name($this->safeModalName),
        ]);
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
                    Wrapper::make([
                        $this->getSidebarComponent(),
                        Div::make([
                            Fragment::make([
                                Flash::make(),
                                $this->getHeaderComponent(),
                                Content::make($this->getContentComponents()),
                                $this->getFooterComponent(),
                            ])->class('layout-page')->name(self::CONTENT_FRAGMENT_NAME),
                        ])->class('flex grow overflow-auto')->customAttributes(['id' => self::CONTENT_ID]),
                    ]),
                ]),
            ])
                ->customAttributes([
                    'lang' => $this->getHeadLang(),
                ])
                ->withAlpineJs()
                ->withThemes($this->isAlwaysDark()),
        ]);
    }
}
