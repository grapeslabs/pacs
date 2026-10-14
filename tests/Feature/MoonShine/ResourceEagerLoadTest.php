<?php

namespace Tests\Feature\MoonShine;

use App\Models\Key;
use App\Models\Person;
use App\Models\Tag;
use App\MoonShine\Resources\PersonResource;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ResourceEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_list_does_not_query_per_row(): void
    {
        Queue::fake();

        $tag = Tag::factory()->create();

        foreach (range(1, 3) as $i) {
            $person = Person::factory()->create();
            $person->tags()->attach($tag);
            Key::factory()->create(['person_id' => $person->id]);
        }

        $queries = [];

        DB::listen(function (QueryExecuted $event) use (&$queries) {
            $queries[] = $event->sql;
        });

        $items = $this->app->make(PersonResource::class)->getQuery()->get();

        foreach ($items as $item) {
            $item->tags->pluck('name')->all();
            $item->organization?->short_name;
            $this->assertSame(1, (int) $item->keys_count);
        }

        $this->assertCount(3, $items);
        $this->assertLessThanOrEqual(3, count($queries), implode("\n", $queries));
    }
}
