<?php

namespace Tests\Feature\MoonShine;

use App\Models\User;
use App\MoonShine\Pages\Dashboard;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardQueriesTest extends TestCase
{
    use RefreshDatabase;

    private function renderQueries(): array
    {
        $queries = [];

        DB::listen(function (QueryExecuted $event) use (&$queries) {
            $queries[] = $event->sql;
        });

        $page = $this->app->make(Dashboard::class);
        $components = new \ReflectionMethod($page, 'components');
        $components->setAccessible(true);
        $components->invoke($page);

        return $queries;
    }

    public function test_stream_counters_are_queried_once_per_render(): void
    {
        Config::set('services.va.enabled', false);

        $this->actingAs(User::factory()->create(), 'moonshine');

        $queries = $this->renderQueries();

        $total = array_filter(
            $queries,
            fn (string $sql) => $sql === 'select count(*) as aggregate from "streams" where "streams"."deleted_at" is null',
        );

        $ai = array_filter($queries, fn (string $sql) => str_contains($sql, 'global_enable'));

        $this->assertCount(1, $total);
        $this->assertCount(0, $ai);
    }
}
