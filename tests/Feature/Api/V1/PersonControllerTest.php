<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Tag;
use App\Services\VideoAnalyticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = ApiKey::factory()->active()->create(['is_unlimited' => true]);
        $this->headers = ['X-API-KEY' => $apiKey->key];
        Storage::fake('public');

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('personCreate')->andReturn([]);
            $mock->shouldReceive('personDelete')->andReturn([]);
        });
    }

    public function test_index_requires_a_valid_api_key(): void
    {
        $this->getJson('/api/v1/persons')->assertStatus(401);
    }

    public function test_index_returns_paginated_list_with_meta(): void
    {
        Person::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/persons?limit=2&offset=0', $this->headers);

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_index_filters_by_last_name_substring(): void
    {
        $target = Person::factory()->create(['last_name' => 'Иванов']);
        Person::factory()->create(['last_name' => 'Петров']);

        $response = $this->getJson('/api/v1/persons?last_name=ванов', $this->headers);

        $this->assertSame([$target->id], array_column($response->json('data'), 'id'));
    }

    public function test_index_filters_by_organization_and_birth_date(): void
    {
        $org = Organization::factory()->create();
        $target = Person::factory()->create(['organization_id' => $org->id, 'birth_date' => '1990-05-15']);
        Person::factory()->create(['organization_id' => null]);

        $byOrg = $this->getJson("/api/v1/persons?organization_id={$org->id}", $this->headers);
        $this->assertSame([$target->id], array_column($byOrg->json('data'), 'id'));

        $byDate = $this->getJson('/api/v1/persons?birth_date=1990-05-15', $this->headers);
        $this->assertContains($target->id, array_column($byDate->json('data'), 'id'));
    }

    public function test_index_filters_by_tags(): void
    {
        $tag = Tag::factory()->create();
        $tagged = Person::factory()->create();
        $tagged->tags()->attach($tag->id);
        Person::factory()->create();

        $response = $this->getJson('/api/v1/persons?'.http_build_query(['tags' => [$tag->id]]), $this->headers);

        $this->assertSame([$tagged->id], array_column($response->json('data'), 'id'));
    }

    public function test_show_returns_person_with_relations(): void
    {
        $org = Organization::factory()->create();
        $person = Person::factory()->create(['organization_id' => $org->id]);
        $tag = Tag::factory()->create();
        $person->tags()->attach($tag->id);

        $response = $this->getJson("/api/v1/persons/{$person->id}", $this->headers);

        $response->assertOk();
        $this->assertSame($org->id, $response->json('organization.id'));
        $this->assertSame([$tag->id], array_column($response->json('tags'), 'id'));
    }

    public function test_show_returns_404_for_unknown_person(): void
    {
        $this->getJson('/api/v1/persons/999999', $this->headers)
            ->assertStatus(404)
            ->assertJson(['message' => 'Персона не найдена']);
    }

    public function test_store_creates_person_with_uploaded_photo(): void
    {
        $photo = UploadedFile::fake()->image('face.jpg');

        $response = $this->postJson('/api/v1/persons', [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'photo' => [$photo],
        ], $this->headers);

        $response->assertStatus(201);
        $stored = Person::findOrFail($response->json('id'));
        $this->assertNotEmpty($stored->photo);
        Storage::disk('public')->assertExists($stored->photo[0]);
        $this->assertStringContainsString($stored->photo[0], $response->json('photo.0'));
    }

    public function test_store_syncs_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/persons', [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'tags' => $tags->pluck('id')->all(),
        ], $this->headers);

        $response->assertStatus(201);
        $returnedTagIds = collect($response->json('tags'))->pluck('id')->sort()->values()->all();
        $this->assertSame($tags->pluck('id')->sort()->values()->all(), $returnedTagIds);
    }

    public function test_store_requires_first_and_last_name(): void
    {
        $this->postJson('/api/v1/persons', [], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_store_rejects_birth_date_in_the_future(): void
    {
        $this->postJson('/api/v1/persons', [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'birth_date' => now()->addDay()->toDateString(),
        ], $this->headers)->assertJsonValidationErrors(['birth_date']);
    }

    public function test_store_rejects_nonexistent_organization_and_tags(): void
    {
        $this->postJson('/api/v1/persons', [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'organization_id' => 999999,
            'tags' => [999999],
        ], $this->headers)->assertJsonValidationErrors(['organization_id', 'tags.0']);
    }

    public function test_update_changes_fields_and_resyncs_tags(): void
    {
        $person = Person::factory()->create(['last_name' => 'Old']);
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $person->tags()->attach($oldTag->id);

        $response = $this->postJson("/api/v1/persons/{$person->id}", [
            'last_name' => 'New',
            'tags' => [$newTag->id],
        ], $this->headers);

        $response->assertOk();
        $this->assertSame('New', $person->fresh()->last_name);
        $this->assertSame([$newTag->id], $person->fresh()->tags->pluck('id')->all());
    }

    public function test_update_rejects_empty_last_name(): void
    {
        $person = Person::factory()->create();

        $this->postJson("/api/v1/persons/{$person->id}", ['last_name' => ''], $this->headers)
            ->assertJsonValidationErrors(['last_name']);
    }

    public function test_update_rejects_null_first_name_with_a_validation_error(): void
    {
        $person = Person::factory()->create();

        $this->postJson("/api/v1/persons/{$person->id}", ['first_name' => null], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);

        $this->assertNotNull($person->fresh()->first_name);
    }

    public function test_update_returns_404_for_unknown_person(): void
    {
        $this->postJson('/api/v1/persons/999999', ['last_name' => 'X'], $this->headers)
            ->assertStatus(404);
    }

    public function test_destroy_deletes_person(): void
    {
        $person = Person::factory()->create();

        $response = $this->deleteJson("/api/v1/persons/{$person->id}", [], $this->headers);

        $response->assertOk()->assertJson(['message' => 'Персона успешно удалена']);
        $this->assertSoftDeleted('person', ['id' => $person->id]);
    }

    public function test_destroy_returns_404_for_unknown_person(): void
    {
        $this->deleteJson('/api/v1/persons/999999', [], $this->headers)->assertStatus(404);
    }
}
