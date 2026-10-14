<?php

namespace Tests\Unit\Services;

use App\Services\MenuService;
use App\Services\PermissionService;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Core\Pages\Pages;
use MoonShine\Core\Resources\Resources;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use Tests\Unit\Services\Fixtures\FakePermissionPage;
use Tests\Unit\Services\Fixtures\FakePermissionResource;

class PermissionServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function makeService(array $categoryMap, array $resources = [], array $pages = []): PermissionService
    {
        $core = \Mockery::mock(CoreContract::class);
        $core->shouldReceive('getResources')->andReturn(new Resources($resources));
        $core->shouldReceive('getPages')->andReturn(new Pages($pages));
        $this->app->instance(CoreContract::class, $core);

        $menuService = \Mockery::mock(MenuService::class);
        $menuService->shouldReceive('permissionCategories')->andReturn($categoryMap);

        return new PermissionService($menuService);
    }

    public function test_resource_entity_gets_full_crud_actions(): void
    {
        $service = $this->makeService(
            categoryMap: ['СКУД' => ['icon' => 'person', 'classes' => [FakePermissionResource::class]]],
            resources: [new FakePermissionResource()],
        );

        $tree = $service->getPermissionTree();

        $this->assertSame(['СКУД'], array_keys($tree));
        $this->assertSame('person', $tree['СКУД']['icon']);
        $this->assertSame([
            'class' => FakePermissionResource::class,
            'name' => 'Персоны',
            'is_page' => false,
            'actions' => [
                'viewAny' => 'Просмотр всех записей',
                'view' => 'Детальный просмотр записи',
                'create' => 'Создание записи',
                'update' => 'Редактирование записи',
                'delete' => 'Удаление записи',
                'massDelete' => 'Массовое удаление записей',
                'restore' => 'Восстановление записи',
                'forceDelete' => 'Перманентное удаление записей',
            ],
        ], $tree['СКУД']['resources'][0]);
    }

    public function test_page_entity_gets_only_view_action(): void
    {
        $service = $this->makeService(
            categoryMap: ['Управление' => ['icon' => 'gear', 'classes' => [FakePermissionPage::class]]],
            pages: [$this->app->make(FakePermissionPage::class)],
        );

        $tree = $service->getPermissionTree();

        $entry = $tree['Управление']['resources'][0];
        $this->assertTrue($entry['is_page']);
        $this->assertSame('Настройки', $entry['name']);
        $this->assertSame(['view' => 'Просмотр страницы'], $entry['actions']);
    }

    public function test_category_missing_icon_falls_back_to_folder(): void
    {
        $service = $this->makeService(
            categoryMap: ['Без иконки' => ['classes' => [FakePermissionResource::class]]],
            resources: [new FakePermissionResource()],
        );

        $tree = $service->getPermissionTree();

        $this->assertSame('folder', $tree['Без иконки']['icon']);
    }

    public function test_category_without_any_registered_entity_is_dropped(): void
    {
        $service = $this->makeService(
            categoryMap: ['Пусто' => ['icon' => 'folder', 'classes' => [FakePermissionResource::class]]],
            resources: [],
        );

        $tree = $service->getPermissionTree();

        $this->assertSame([], $tree);
    }

    public function test_only_registered_classes_within_a_category_are_included(): void
    {
        $service = $this->makeService(
            categoryMap: ['СКУД' => [
                'icon' => 'person',
                'classes' => [FakePermissionResource::class, FakePermissionPage::class],
            ]],
            resources: [new FakePermissionResource()],
        );

        $tree = $service->getPermissionTree();

        $this->assertCount(1, $tree['СКУД']['resources']);
        $this->assertSame(FakePermissionResource::class, $tree['СКУД']['resources'][0]['class']);
    }

    public function test_multiple_categories_are_preserved_independently(): void
    {
        $service = $this->makeService(
            categoryMap: [
                'СКУД' => ['icon' => 'person', 'classes' => [FakePermissionResource::class]],
                'Управление' => ['icon' => 'gear', 'classes' => [FakePermissionPage::class]],
            ],
            resources: [new FakePermissionResource()],
            pages: [$this->app->make(FakePermissionPage::class)],
        );

        $tree = $service->getPermissionTree();

        $this->assertSame(['СКУД', 'Управление'], array_keys($tree));
    }
}
