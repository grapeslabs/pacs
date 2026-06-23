<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Bot;
use App\Models\Car;
use App\Models\CarPassageRule;
use App\Models\Guest;
use App\Models\Key;
use App\Models\Organization;
use App\Models\Passage;
use App\Models\Person;
use App\Models\Role;
use App\Models\Stream;
use App\Models\Trigger;
use App\Models\User;
use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Resources\ActuatorDeviceResource;
use App\MoonShine\Resources\ApiKeyResource;
use App\MoonShine\Resources\BotResource;
use App\MoonShine\Resources\CarBrandResource;
use App\MoonShine\Resources\CarColorResource;
use App\MoonShine\Resources\CarEventResource;
use App\MoonShine\Resources\CarPassageRuleResource;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\ControllerResource;
use App\MoonShine\Resources\EventReportResource;
use App\MoonShine\Resources\GuestResource;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\OrganizationResource;
use App\MoonShine\Resources\PassageEventResource;
use App\MoonShine\Resources\PassageResource;
use App\MoonShine\Resources\PeopleReportResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\SkudEventResource;
use App\MoonShine\Resources\TerminalResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\UnknownReportResource;
use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Pages\Page;

class MenuService
{
    public function home(): array
    {
        return [
            'label' => 'Главная',
            'class' => Dashboard::class,
            'icon' => self::icon('home'),
        ];
    }

    public function sections(): array
    {
        return [
            [
                'label' => 'Управление',
                'sectionIcon' => self::permIcon('settings'),
                'items' => [
                    ['label' => 'Пользователи', 'class' => MoonShineUserResource::class, 'icon' => self::icon('users'), 'model' => User::class],
                    ['label' => 'Роли', 'class' => MoonShineUserRoleResource::class, 'icon' => self::icon('shield'), 'model' => Role::class],
                    ['label' => 'Ключи API', 'class' => ApiKeyResource::class, 'icon' => self::icon('code'), 'model' => ApiKey::class],
                    ['label' => 'Настройки', 'class' => SettingsPage::class, 'icon' => self::icon('cog'), 'feature' => 'services.ms.enabled'],
                ],
            ],
            [
                'label' => 'Видео',
                'sectionIcon' => self::permIcon('streams'),
                'feature' => 'services.ms.enabled',
                'items' => [
                    ['label' => 'Видеопотоки', 'class' => VideoStreamResource::class, 'icon' => self::icon('camera'), 'model' => Stream::class],
                ],
            ],
            [
                'label' => 'Данные СКУД',
                'sectionIcon' => self::permIcon('skud'),
                'items' => [
                    ['label' => 'Персоны', 'class' => PersonResource::class, 'icon' => self::icon('user'), 'model' => Person::class],
                    ['label' => 'Организации', 'class' => OrganizationResource::class, 'icon' => self::icon('building'), 'model' => Organization::class],
                    ['label' => 'Ключи', 'class' => KeyResource::class, 'icon' => self::icon('key'), 'model' => Key::class],
                    ['label' => 'Автомобили', 'class' => CarResource::class, 'icon' => self::icon('car'), 'model' => Car::class],
                    ['label' => 'Проезды', 'class' => PassageResource::class, 'icon' => self::icon('route'), 'model' => Passage::class],
                    ['label' => 'Правила проезда', 'class' => CarPassageRuleResource::class, 'icon' => self::icon('list'), 'model' => CarPassageRule::class],
                    ['label' => 'Гости', 'class' => GuestResource::class, 'icon' => self::icon('user'), 'model' => Guest::class],
                ],
            ],
            [
                'label' => 'Автоматизация',
                'sectionIcon' => self::permIcon('triggers'),
                'feature' => 'services.va.enabled',
                'items' => [
                    ['label' => 'Боты', 'class' => BotResource::class, 'icon' => self::icon('bot'), 'model' => Bot::class],
                    ['label' => 'Триггеры', 'class' => TriggerResource::class, 'icon' => self::icon('bolt'), 'model' => Trigger::class],
                ],
            ],
            [
                'label' => 'Отчёты',
                'sectionIcon' => self::permIcon('reports'),
                'items' => [
                    ['label' => 'Отчёты СКУД', 'class' => SkudEventResource::class, 'icon' => self::icon('document')],
                    ['label' => 'Отчёты автомобилей', 'class' => CarEventResource::class, 'icon' => self::icon('table'), 'feature' => 'services.lpr.enabled'],
                    ['label' => 'Отчёты проезда', 'class' => PassageEventResource::class, 'icon' => self::icon('document'), 'feature' => 'services.lpr.enabled'],
                    ['label' => 'Отчёты по событиям', 'class' => EventReportResource::class, 'icon' => self::icon('smile'), 'feature' => 'services.va.enabled'],
                    ['label' => 'Отчёты по персонам', 'class' => PeopleReportResource::class, 'icon' => self::icon('user'), 'feature' => 'services.va.enabled'],
                    ['label' => 'Отчёты по неизвестным', 'class' => UnknownReportResource::class, 'icon' => self::icon('warning'), 'feature' => 'services.va.enabled'],
                ],
            ],
            [
                'label' => 'Оборудование',
                'sectionIcon' => self::permIcon('devices'),
                'items' => [
                    ['label' => 'Терминалы доступа', 'class' => TerminalResource::class, 'icon' => self::icon('device')],
                    ['label' => 'Контроллеры СКУД', 'class' => ControllerResource::class, 'icon' => self::icon('cpu')],
                    ['label' => 'Исполнительные устройства', 'class' => ActuatorDeviceResource::class, 'icon' => self::icon('plug')],
                ],
            ],
            [
                'label' => 'Справочники',
                'sectionIcon' => self::permIcon('references'),
                'items' => [
                    ['label' => 'Марки автомобилей', 'class' => CarBrandResource::class, 'icon' => self::icon('tag')],
                    ['label' => 'Цвета автомобилей', 'class' => CarColorResource::class, 'icon' => self::icon('swatch')],
                ],
            ],
        ];
    }

    public function visibleMenu(?User $user): array
    {
        $home = $this->home();
        $home['url'] = $this->url($home['class']);
        $home['active'] = $this->isActive($home['url'], true);

        $sections = [];

        foreach ($this->sections() as $section) {
            if (! $this->featureEnabled($section['feature'] ?? null)) {
                continue;
            }

            $items = [];

            foreach ($section['items'] as $item) {
                if (! $this->featureEnabled($item['feature'] ?? null)) {
                    continue;
                }

                if (! $this->canSee($user, $item)) {
                    continue;
                }

                $url = $this->url($item['class']);

                $items[] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => $url,
                    'active' => $this->isActive($url, false),
                    'badge' => isset($item['model']) ? $this->count($item['model']) : null,
                ];
            }

            if ($items !== []) {
                $sections[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return ['home' => $home, 'sections' => $sections];
    }

    public function permissionCategories(): array
    {
        $categories = [];

        foreach ($this->sections() as $section) {
            $categories[$section['label']] = [
                'icon' => $section['sectionIcon'] ?? 'folder',
                'classes' => array_map(static fn (array $item): string => $item['class'], $section['items']),
            ];
        }

        return $categories;
    }

    private function canSee(?User $user, array $item): bool
    {
        if ($user === null) {
            return false;
        }

        $ability = ($item['ability'] ?? null)
            ?? (is_subclass_of($item['class'], Page::class) ? Ability::VIEW : Ability::VIEW_ANY);

        return $user->hasPermission($item['class'], $ability);
    }

    private function featureEnabled(?string $feature): bool
    {
        return $feature === null || (bool) config($feature);
    }

    private function url(string $class): string
    {
        try {
            if (is_subclass_of($class, Page::class)) {
                return toPage($class);
            }

            return app($class)->getUrl();
        } catch (\Throwable) {
            return '#';
        }
    }

    private function isActive(string $url, bool $isHome): bool
    {
        if ($url === '#') {
            return false;
        }

        $current = rtrim((string) request()->url(), '/');
        $target = rtrim($url, '/');

        return $isHome ? $current === $target : str_starts_with($current, $target);
    }

    private function count(string $model): ?int
    {
        try {
            return $model::count();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function permIcon(string $name): string
    {
        $path = public_path("icons/Permissions/{$name}.svg");

        if (! is_file($path)) {
            $fallback = public_path("icons/menu-{$name}.svg");
            $path = is_file($fallback) ? $fallback : null;
        }

        return $path !== null ? (string) file_get_contents($path) : 'folder';
    }

    private static function icon(string $name): string
    {
        $paths = [
            'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/>',
            'users' => '<path d="M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.2a3.5 3.5 0 0 1 0 6.6"/><path d="M18 13.5a6.5 6.5 0 0 1 3.5 5.8"/>',
            'shield' => '<path d="M12 3 5 6v5c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6z"/>',
            'code' => '<path d="m9 8-4 4 4 4"/><path d="m15 8 4 4-4 4"/>',
            'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l1.7-1.3-2-3.4-2 .8a7.6 7.6 0 0 0-2.6-1.5L14 2h-4l-.4 2.1A7.6 7.6 0 0 0 7 5.6l-2-.8-2 3.4L4.6 9.5a7.6 7.6 0 0 0 0 3l-1.7 1.3 2 3.4 2-.8a7.6 7.6 0 0 0 2.6 1.5L10 22h4l.4-2.1a7.6 7.6 0 0 0 2.6-1.5l2 .8 2-3.4z"/>',
            'camera' => '<rect x="3" y="6.5" width="12" height="11" rx="2"/><path d="m15 10.5 6-3.5v10l-6-3.5"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
            'building' => '<rect x="5" y="3" width="14" height="18" rx="1.5"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/><path d="M10 21v-3h4v3"/>',
            'key' => '<circle cx="8" cy="8" r="4"/><path d="m11 11 8 8"/><path d="m16 16 2-2M18.5 18.5l2-2"/>',
            'car' => '<path d="M3 13.5 5 8a2 2 0 0 1 1.9-1.3h10.2A2 2 0 0 1 19 8l2 5.5"/><path d="M3 13.5h18V18a1 1 0 0 1-1 1h-1.5a1 1 0 0 1-1-1v-1h-9v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M6.5 16h.01M17.5 16h.01"/>',
            'route' => '<circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path d="M8.5 18H15a3.5 3.5 0 0 0 0-7H9a3.5 3.5 0 0 1 0-7"/>',
            'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
            'bot' => '<rect x="4" y="8" width="16" height="11" rx="2.5"/><path d="M12 8V4.5M9.5 13h.01M14.5 13h.01"/><path d="M2 12.5v3M22 12.5v3"/>',
            'bolt' => '<path d="M13 3 4 14h7l-1 7 9-11h-7z"/>',
            'document' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/><path d="M9 12h6M9 16h6"/>',
            'table' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M9 5v14"/>',
            'smile' => '<circle cx="12" cy="12" r="9"/><path d="M8.5 14a4 4 0 0 0 7 0"/><path d="M9 9.5h.01M15 9.5h.01"/>',
            'warning' => '<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/>',
            'device' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 6h2M10.5 18h3"/>',
            'cpu' => '<rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M10 7V4M14 7V4M10 20v-3M14 20v-3M7 10H4M7 14H4M20 10h-3M20 14h-3"/>',
            'plug' => '<path d="M9 3v5M15 3v5"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v4"/>',
            'tag' => '<path d="M3 11.5 11.5 3H20v8.5L11.5 20z"/><circle cx="15.5" cy="8.5" r="1.5"/>',
            'swatch' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        ];

        $body = $paths[$name] ?? '<circle cx="12" cy="12" r="8"/>';

        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
    }
}
