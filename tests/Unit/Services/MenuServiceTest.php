<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Pages\LogViewerPage;
use App\MoonShine\Resources\PersonResource;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MenuServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): MenuService
    {
        return $this->app->make(MenuService::class);
    }

    private function superAdmin(): User
    {
        return (new User())->forceFill(['id' => 1]);
    }

    public function test_home_points_to_dashboard(): void
    {
        $home = $this->service()->home();

        $this->assertSame('Главная', $home['label']);
        $this->assertSame(Dashboard::class, $home['class']);
        $this->assertNotSame('', $home['icon']);
    }

    public function test_permission_categories_is_a_faithful_projection_of_sections(): void
    {
        $service = $this->service();
        $categories = $service->permissionCategories();

        foreach ($service->sections() as $section) {
            $this->assertArrayHasKey($section['label'], $categories);
            $this->assertSame($section['sectionIcon'], $categories[$section['label']]['icon']);
            $this->assertSame(
                array_column($section['items'], 'class'),
                $categories[$section['label']]['classes'],
            );
        }
    }

    public function test_guest_sees_home_but_no_sections(): void
    {
        $menu = $this->service()->visibleMenu(null);

        $this->assertSame(Dashboard::class, $menu['home']['class']);
        $this->assertArrayHasKey('url', $menu['home']);
        $this->assertSame([], $menu['sections']);
    }

    public function test_super_admin_sees_every_enabled_section_and_item(): void
    {
        $service = $this->service();

        $menu = $service->visibleMenu($this->superAdmin());

        $expected = collect($service->sections())
            ->mapWithKeys(fn (array $section) => [$section['key'] => array_column($section['items'], 'label')])
            ->all();

        $actual = collect($menu['sections'])
            ->mapWithKeys(fn (array $section) => [$section['key'] => array_column($section['items'], 'label')])
            ->all();

        $this->assertSame($expected, $actual);
    }

    public function test_super_admin_badge_counts_are_resolved_from_the_database(): void
    {
        $menu = $this->service()->visibleMenu($this->superAdmin());

        $skud = collect($menu['sections'])->firstWhere('key', 'skud');
        $persons = collect($skud['items'])->firstWhere('label', 'Персоны');

        $this->assertSame(0, $persons['badge']);
    }

    public function test_super_admin_page_items_resolve_to_a_real_url(): void
    {
        $menu = $this->service()->visibleMenu($this->superAdmin());

        $management = collect($menu['sections'])->firstWhere('key', 'management');
        $logs = collect($management['items'])->firstWhere('label', 'Журнал логов');

        $this->assertNotSame('#', $logs['url']);
        $this->assertStringContainsString('log-viewer-page', $logs['url']);
    }

    public function test_disabled_feature_hides_whole_section_even_for_super_admin(): void
    {
        Config::set('services.ms.enabled', false);

        $menu = $this->service()->visibleMenu($this->superAdmin());

        $keys = array_column($menu['sections'], 'key');

        $this->assertNotContains('video', $keys);
        $this->assertContains('skud', $keys);
    }

    public function test_item_level_feature_flag_hides_only_that_item(): void
    {
        Config::set('services.lpr.enabled', false);

        $menu = $this->service()->visibleMenu($this->superAdmin());

        $reports = collect($menu['sections'])->firstWhere('key', 'reports');
        $labels = array_column($reports['items'], 'label');

        $this->assertNotContains('Отчёты автомобилей', $labels);
        $this->assertNotContains('Отчёты проезда', $labels);
        $this->assertContains('Отчёты СКУД', $labels);
        $this->assertContains('Отчёты по событиям', $labels);
    }

    public function test_restricted_user_sees_only_explicitly_permitted_item(): void
    {
        $user = new User([
            'permissions' => [
                PersonResource::class => ['viewAny' => true],
            ],
        ]);

        $menu = $this->service()->visibleMenu($user);

        $this->assertCount(1, $menu['sections']);
        $this->assertSame('skud', $menu['sections'][0]['key']);
        $this->assertSame(['Персоны'], array_column($menu['sections'][0]['items'], 'label'));
    }

    public function test_section_label_for_resolves_resource_and_nested_paths(): void
    {
        $service = $this->service();
        $baseUrl = $this->app->make(PersonResource::class)->getUrl();

        $this->assertSame('Данные СКУД', $service->sectionLabelFor($baseUrl));
        $this->assertSame('Данные СКУД', $service->sectionLabelFor($baseUrl.'/123/edit'));
    }

    public function test_section_label_for_resolves_page_url(): void
    {
        $service = $this->service();
        $url = toPage(LogViewerPage::class);

        $this->assertSame('Управление', $service->sectionLabelFor($url));
    }

    public function test_section_label_for_returns_null_for_unknown_or_empty_path(): void
    {
        $service = $this->service();

        $this->assertNull($service->sectionLabelFor('http://localhost/no-such-path'));
        $this->assertNull($service->sectionLabelFor('http://localhost/'));
    }
}
