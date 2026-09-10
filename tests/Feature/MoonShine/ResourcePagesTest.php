<?php

namespace Tests\Feature\MoonShine;

use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Resources\ModelResource;
use Tests\TestCase;

class ResourcePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_registered_resource_keeps_the_custom_index_page(): void
    {
        $checked = 0;

        foreach (moonshine()->getResources() as $resource) {
            if (! $resource instanceof ModelResource) {
                continue;
            }

            $checked++;

            $this->assertInstanceOf(
                CustomIndexPage::class,
                $resource->getIndexPage(),
                $resource::class . ' потерял CustomIndexPage',
            );
        }

        $this->assertGreaterThan(20, $checked);
    }
}
