<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Key;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = ApiKey::factory()->active()->create(['is_unlimited' => true]);
        $this->headers = ['X-API-KEY' => $apiKey->key];
    }

    public function test_index_requires_a_valid_api_key(): void
    {
        $this->getJson('/api/v1/keys')->assertStatus(401);
    }

    public function test_index_returns_paginated_list_with_meta(): void
    {
        $person = Person::factory()->create();
        Key::factory()->count(3)->create(['person_id' => $person->id]);

        $response = $this->getJson('/api/v1/keys?limit=2&offset=0', $this->headers);

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $this->assertSame(3, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.limit'));
        $this->assertSame(0, $response->json('meta.offset'));
    }

    public function test_index_filters_by_person_id_type_and_key_substring(): void
    {
        $personA = Person::factory()->create();
        $personB = Person::factory()->create();
        $target = Key::factory()->create(['person_id' => $personA->id, 'key' => 'ABCDEF01', 'type' => 'Mifare']);
        Key::factory()->create(['person_id' => $personB->id, 'key' => 'ZZZZZZZZ', 'type' => 'Mifare']);

        $byPerson = $this->getJson("/api/v1/keys?person_id={$personA->id}", $this->headers);
        $this->assertSame([$target->id], array_column($byPerson->json('data'), 'id'));

        $bySubstring = $this->getJson('/api/v1/keys?key=CDEF', $this->headers);
        $this->assertSame([$target->id], array_column($bySubstring->json('data'), 'id'));
    }

    public function test_show_returns_key_with_person_relation(): void
    {
        $person = Person::factory()->create();
        $key = Key::factory()->create(['person_id' => $person->id]);

        $response = $this->getJson("/api/v1/keys/{$key->id}", $this->headers);

        $response->assertOk();
        $this->assertSame($key->id, $response->json('id'));
        $this->assertSame($person->id, $response->json('person.id'));
    }

    public function test_show_returns_404_for_unknown_key(): void
    {
        $this->getJson('/api/v1/keys/999999', $this->headers)->assertStatus(404);
    }

    public function test_show_returns_404_for_soft_deleted_key(): void
    {
        $key = Key::factory()->create();
        $key->delete();

        $this->getJson("/api/v1/keys/{$key->id}", $this->headers)->assertStatus(404);
    }

    public function test_store_creates_key(): void
    {
        $person = Person::factory()->create();

        $response = $this->postJson('/api/v1/keys', [
            'key' => 'NEWKEY01',
            'type' => 'Mifare',
            'person_id' => $person->id,
        ], $this->headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('keys', ['key' => 'NEWKEY01', 'person_id' => $person->id]);
    }

    public function test_store_requires_key_type_and_person_id(): void
    {
        $this->postJson('/api/v1/keys', [], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'type', 'person_id']);
    }

    public function test_store_rejects_type_outside_allowed_list(): void
    {
        $person = Person::factory()->create();

        $this->postJson('/api/v1/keys', [
            'key' => 'ABC123',
            'type' => 'NotAType',
            'person_id' => $person->id,
        ], $this->headers)->assertJsonValidationErrors(['type']);
    }

    public function test_store_rejects_nonexistent_person(): void
    {
        $this->postJson('/api/v1/keys', [
            'key' => 'ABC123',
            'type' => 'Mifare',
            'person_id' => 999999,
        ], $this->headers)->assertJsonValidationErrors(['person_id']);
    }

    public function test_store_rejects_duplicate_key_value(): void
    {
        $person = Person::factory()->create();
        Key::factory()->create(['key' => 'DUPLICATE']);

        $this->postJson('/api/v1/keys', [
            'key' => 'DUPLICATE',
            'type' => 'Mifare',
            'person_id' => $person->id,
        ], $this->headers)->assertJsonValidationErrors(['key']);
    }

    public function test_store_allows_reusing_the_value_of_a_soft_deleted_key(): void
    {
        $deleted = Key::factory()->create(['key' => 'WAS-DELETED']);
        $deleted->delete();
        $person = Person::factory()->create();

        $this->postJson('/api/v1/keys', [
            'key' => 'WAS-DELETED',
            'type' => 'Mifare',
            'person_id' => $person->id,
        ], $this->headers)->assertStatus(201);

        $this->assertSame(1, Key::query()->where('key', 'WAS-DELETED')->count());
        $this->assertSame(2, Key::withTrashed()->where('key', 'WAS-DELETED')->count());
    }

    public function test_update_changes_only_provided_fields(): void
    {
        $person = Person::factory()->create();
        $key = Key::factory()->create(['person_id' => $person->id, 'key' => 'OLDVAL', 'type' => 'Mifare']);

        $response = $this->postJson("/api/v1/keys/{$key->id}", ['key' => 'NEWVAL'], $this->headers);

        $response->assertOk();
        $this->assertSame('NEWVAL', $key->fresh()->key);
        $this->assertSame($person->id, $key->fresh()->person_id);
    }

    public function test_update_allows_keeping_its_own_current_key_value(): void
    {
        $key = Key::factory()->create(['key' => 'SAME-VALUE']);

        $this->postJson("/api/v1/keys/{$key->id}", ['key' => 'SAME-VALUE'], $this->headers)
            ->assertOk();
    }

    public function test_update_rejects_key_value_taken_by_another_key(): void
    {
        $taken = Key::factory()->create(['key' => 'TAKEN']);
        $key = Key::factory()->create(['key' => 'MINE']);

        $this->postJson("/api/v1/keys/{$key->id}", ['key' => 'TAKEN'], $this->headers)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_destroy_soft_deletes_the_key(): void
    {
        $key = Key::factory()->create();

        $response = $this->deleteJson("/api/v1/keys/{$key->id}", [], $this->headers);

        $response->assertOk();
        $this->assertSoftDeleted('keys', ['id' => $key->id]);
    }
}
