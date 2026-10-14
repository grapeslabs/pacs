<?php

namespace Tests\Feature\Api\V1;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_public_and_requires_no_api_key(): void
    {
        Organization::factory()->count(2)->create();

        $this->getJson('/api/v1/organizations')->assertOk();
    }

    public function test_index_returns_only_the_whitelisted_fields(): void
    {
        $org = Organization::factory()->create();

        $response = $this->getJson('/api/v1/organizations');

        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('id', $org->id);
        $this->assertSame(['id', 'full_name', 'short_name', 'address'], array_keys($item));
    }

    public function test_index_paginates_using_per_page(): void
    {
        Organization::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/organizations?per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(2, $response->json('meta.per_page'));
        $this->assertSame(5, $response->json('meta.total'));
        $this->assertSame(3, $response->json('meta.last_page'));
    }

    public function test_index_next_and_prev_links_reflect_current_page(): void
    {
        Organization::factory()->count(5)->create();

        $firstPage = $this->getJson('/api/v1/organizations?per_page=2&page=1');
        $this->assertNull($firstPage->json('links.prev'));
        $this->assertStringContainsString('page=2', $firstPage->json('links.next'));

        $lastPage = $this->getJson('/api/v1/organizations?per_page=2&page=3');
        $this->assertNull($lastPage->json('links.next'));
        $this->assertStringContainsString('page=2', $lastPage->json('links.prev'));
    }

    public function test_index_rejects_invalid_pagination_params(): void
    {
        $this->getJson('/api/v1/organizations?per_page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/v1/organizations?per_page=1001')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/v1/organizations?page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }
}
