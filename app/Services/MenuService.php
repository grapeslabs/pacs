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
use App\Models\PersonPassageRule;
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
use App\MoonShine\Resources\PersonPassageRuleResource;
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
            'icon' => $this->iconFile('home'),
        ];
    }

    public function sections(): array
    {
        return [
            [
                'label' => 'Видео',
                'sectionIcon' => $this->iconFile('camera'),
                'feature' => 'services.ms.enabled',
                'items' => [
                    ['label' => 'Видеопотоки', 'class' => VideoStreamResource::class, 'icon' => $this->iconFile('camera'), 'model' => Stream::class],
                ],
            ],
            [
                'label' => 'Данные СКУД',
                'sectionIcon' => $this->iconFile('btn-addkey'),
                'items' => [
                    ['label' => 'Персоны', 'class' => PersonResource::class, 'icon' => $this->iconFile('person'), 'model' => Person::class],
                    ['label' => 'Организации', 'class' => OrganizationResource::class, 'icon' => $this->iconFile('organization'), 'model' => Organization::class],
                    ['label' => 'Ключи', 'class' => KeyResource::class, 'icon' => $this->iconFile('btn-addkey'), 'model' => Key::class],
                    ['label' => 'Автомобили', 'class' => CarResource::class, 'icon' => $this->iconFile('car'), 'model' => Car::class],
                    ['label' => 'Проезды', 'class' => PassageResource::class, 'icon' => $this->iconFile('route'), 'model' => Passage::class],
                    ['label' => 'Правила проезда', 'class' => CarPassageRuleResource::class, 'icon' => $this->iconFile('list'), 'model' => CarPassageRule::class],
                    ['label' => 'Правила прохода', 'class' => PersonPassageRuleResource::class, 'icon' => $this->iconFile('list'), 'model' => PersonPassageRule::class],
                    ['label' => 'Гости', 'class' => GuestResource::class, 'icon' => $this->iconFile('person'), 'model' => Guest::class],
                ],
            ],
            [
                'label' => 'Автоматизация',
                'sectionIcon' => $this->iconFile('lightning'),
                'feature' => 'services.va.enabled',
                'items' => [
                    ['label' => 'Боты', 'class' => BotResource::class, 'icon' => $this->iconFile('bot'), 'model' => Bot::class],
                    ['label' => 'Триггеры', 'class' => TriggerResource::class, 'icon' => $this->iconFile('lightning'), 'model' => Trigger::class],
                ],
            ],
            [
                'label' => 'Отчёты',
                'sectionIcon' => self::permIcon('reports'),
                'items' => [
                    ['label' => 'Отчёты СКУД', 'class' => SkudEventResource::class, 'icon' => $this->iconFile('document')],
                    ['label' => 'Отчёты автомобилей', 'class' => CarEventResource::class, 'icon' => $this->iconFile('plate'), 'feature' => 'services.lpr.enabled'],
                    ['label' => 'Отчёты проезда', 'class' => PassageEventResource::class, 'icon' => $this->iconFile('document'), 'feature' => 'services.lpr.enabled'],
                    ['label' => 'Отчёты по событиям', 'class' => EventReportResource::class, 'icon' => $this->iconFile('pulse'), 'feature' => 'services.va.enabled'],
                    ['label' => 'Отчёты по персонам', 'class' => PeopleReportResource::class, 'icon' => $this->iconFile('user-list'), 'feature' => 'services.va.enabled'],
                    ['label' => 'Отчёты по неизвестным', 'class' => UnknownReportResource::class, 'icon' => $this->iconFile('suspension'), 'feature' => 'services.va.enabled'],
                ],
            ],
            [
                'label' => 'Оборудование',
                'sectionIcon' => self::permIcon('devices'),
                'items' => [
                    ['label' => 'Терминалы доступа', 'class' => TerminalResource::class, 'icon' => $this->iconFile('terminal')],
                    ['label' => 'Контроллеры СКУД', 'class' => ControllerResource::class, 'icon' => $this->iconFile('computer')],
                    ['label' => 'Исполнительные устройства', 'class' => ActuatorDeviceResource::class, 'icon' => $this->iconFile('plug')],
                ],
            ],
            [
                'label' => 'Справочники',
                'sectionIcon' => $this->iconFile('menu-references'),
                'items' => [
                    ['label' => 'Марки автомобилей', 'class' => CarBrandResource::class, 'icon' => $this->iconFile('list')],
                    ['label' => 'Цвета автомобилей', 'class' => CarColorResource::class, 'icon' => $this->iconFile('tag')],
                ],
            ],
            [
                'label' => 'Управление',
                'sectionIcon' => $this->iconFile('gear'),
                'items' => [
                    ['label' => 'Пользователи', 'class' => MoonShineUserResource::class, 'icon' => $this->iconFile('persons'), 'model' => User::class],
                    ['label' => 'Роли', 'class' => MoonShineUserRoleResource::class, 'icon' => $this->iconFile('group'), 'model' => Role::class],
                    ['label' => 'Ключи API', 'class' => ApiKeyResource::class, 'icon' => $this->iconFile('key'), 'model' => ApiKey::class],
                    ['label' => 'Настройки', 'class' => SettingsPage::class, 'icon' => $this->iconFile('gear'), 'feature' => 'services.ms.enabled'],
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
            if (!$this->featureEnabled($section['feature'] ?? null)) {
                continue;
            }

            $items = [];

            foreach ($section['items'] as $item) {
                if (!$this->featureEnabled($item['feature'] ?? null)) {
                    continue;
                }

                if (!$this->canSee($user, $item)) {
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
                'classes' => array_map(static fn(array $item): string => $item['class'], $section['items']),
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
        return $feature === null || (bool)config($feature);
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

    /**
     * Название раздела меню (напр. «Данные СКУД»), которому принадлежит URL, — для хлебных крошек.
     */
    public function sectionLabelFor(string $url): ?string
    {
        $path = rtrim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');

        if ($path === '') {
            return null;
        }

        foreach ($this->sections() as $section) {
            foreach ($section['items'] as $item) {
                $target = rtrim((string) (parse_url($this->url($item['class']), PHP_URL_PATH) ?: ''), '/');

                if ($target !== '' && ($path === $target || str_starts_with($path, $target . '/'))) {
                    return $section['label'];
                }
            }
        }

        return null;
    }

    private function isActive(string $url, bool $isHome): bool
    {
        if ($url === '#') {
            return false;
        }

        // Сравниваем только path: за прокси (https) схема/хост текущего запроса
        // и сгенерированного URL могут различаться, и подсветка пропадает
        $current = rtrim((string) (parse_url((string) request()->url(), PHP_URL_PATH) ?: '/'), '/');
        $target = rtrim((string) (parse_url($url, PHP_URL_PATH) ?: '/'), '/');

        if ($target === '') {
            return false;
        }

        return $isHome
            ? $current === $target
            : ($current === $target || str_starts_with($current, $target . '/'));
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

        if (!is_file($path)) {
            $fallback = public_path("icons/menu-{$name}.svg");
            $path = is_file($fallback) ? $fallback : null;
        }

        return $path !== null ? (string)file_get_contents($path) : 'folder';
    }

    protected function iconFile(string $name): string
    {
        static $cache = [];
        return $cache[$name] ??= (string)@file_get_contents(public_path("icons/{$name}.svg"));
    }
}
