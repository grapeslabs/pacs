<?php

namespace Tests\Feature\MoonShine;

use App\Models\User;
use App\MoonShine\Resources\KeyResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsyncMethodAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function methodUrl(string $method): string
    {
        $resource = app(KeyResource::class);

        return $resource->getAsyncMethodUrl($method, page: $resource->getIndexPage());
    }

    public function test_method_outside_the_map_is_rejected_even_for_super_admin(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isSuper());

        $this->actingAs($user, 'moonshine')
            ->get($this->methodUrl('asyncMethodAbilities'))
            ->assertForbidden();
    }

    public function test_method_declared_in_the_map_is_reachable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'moonshine')
            ->get($this->methodUrl('toggleBlock'))
            ->assertOk();
    }

    public function test_anonymous_request_never_reaches_the_method(): void
    {
        $this->get($this->methodUrl('toggleBlock'))->assertRedirect('/login');
    }
}
