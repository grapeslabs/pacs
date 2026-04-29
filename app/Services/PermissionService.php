<?php

namespace App\Services;

use App\Models\CarColor;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Resources\ApiKeyResource;
use App\MoonShine\Resources\BarrierEventResource;
use App\MoonShine\Resources\BarrierResource;
use App\MoonShine\Resources\BotResource;
use App\MoonShine\Resources\CarBrandResource;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\ControllerResource;
use App\MoonShine\Resources\EventReportResource;
use App\MoonShine\Resources\GuestResource;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\OrganizationResource;
use App\MoonShine\Resources\PeopleReportResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\SkudEventResource;
use App\MoonShine\Resources\TerminalResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\UnknownReportResource;
use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\Core\Pages\Page;

class PermissionService
{
    protected array $categoryMap;

    public function __construct()
    {
        $this->categoryMap = [
            'Пользователи' => [
                'icon' => file_get_contents(public_path('icons/Permissions/persons.svg')),
                'classes' => [MoonShineUserResource::class, MoonShineUserRoleResource::class]
            ],
            'Настройки' => [
                'icon' => 'cog-8-tooth',
                'classes' => [SettingsPage::class]
            ],
            'Видеопотоки' => [
                'icon' => file_get_contents(public_path('icons/Permissions/streams.svg')),
                'classes' => [VideoStreamResource::class]
            ],
            'Данные СКУД'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/skud.svg')),
                'classes' => [PersonResource::class, OrganizationResource::class, KeyResource::class]
            ],
            'Боты'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/bots.svg')),
                'classes' => [BotResource::class]
            ],
            'Триггеры'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/triggers.svg')),
                'classes' => [TriggerResource::class]
            ],
            'Отчёты'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/reports.svg')),
                'classes' => [SkudEventResource::class, EventReportResource::class, PeopleReportResource::class, UnknownReportResource::class]
            ],
            'Оборудование'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/devices.svg')),
                'classes' => [TerminalResource::class, BarrierResource::class, ControllerResource::class]
            ],
            'Автомобили'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/cars.svg')),
                'classes' => [CarResource::class, CarBrandResource::class, CarColor::class, BarrierEventResource::class]
            ],
            'Доступ гостей'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/guests.svg')),
                'classes' => [GuestResource::class]
            ],
            'Ключи API'  => [
                'icon' => file_get_contents(public_path('icons/Permissions/apikeys.svg')),
                'classes' => [ApiKeyResource::class]
            ],
        ];
    }

    public function getPermissionTree(): array
    {
        $tree = [];
        $entities = $this->getAllEntities();

        $resourceActions = [
            'viewAny' => 'Просмотр всех записей',
            'view' => 'Детальный просмотр записи',
            'create' => 'Создание записи',
            'update' => 'Редактирование записи',
            'delete' => 'Удаление записи',
            'massDelete' => 'Массовое удаление записей',
            'restore' => 'Восстановление записи',
            'forceDelete' => 'Перманентное удаление записей',
        ];

        $pageActions = [
            'view' => 'Просмотр страницы',
        ];

        foreach ($this->categoryMap as $category => $data) {
            $tree[$category] = [
                'icon' => $data['icon'] ?? 'folder',
                'resources' => []
            ];

            foreach ($data['classes'] as $className) {
                if (isset($entities[$className])) {
                    $entity = $entities[$className];
                    $isPage = $entity instanceof Page;

                    $tree[$category]['resources'][] = [
                        'class' => $className,
                        'name' => $entity->getTitle(),
                        'is_page' => $isPage,
                        'actions' => $isPage ? $pageActions : $resourceActions
                    ];
                }
            }

            if (empty($tree[$category]['resources'])) {
                unset($tree[$category]);
            }
        }

        return $tree;
    }

    protected function getAllEntities(): array
    {
        $entities = [];

        if (function_exists('moonshine')) {
            $resources = moonshine()->getResources();
            if (is_iterable($resources)) {
                foreach ($resources as $resource) {
                    $entities[get_class($resource)] = $resource;
                }
            }

            $pages = moonshine()->getPages();
            if (is_iterable($pages)) {
                foreach ($pages as $page) {
                    $entities[get_class($page)] = $page;
                }
            }
        }

        return $entities;
    }
}
