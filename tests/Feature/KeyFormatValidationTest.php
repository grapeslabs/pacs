<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Key;
use App\Models\Person;
use App\Models\User;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\PersonResource;
use App\Observers\PersonObserver;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KeyFormatValidationTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $apiKey = ApiKey::factory()->active()->create(['is_unlimited' => true]);
        $this->headers = ['X-API-KEY' => $apiKey->key];
    }

    public static function forbiddenKeys(): array
    {
        return [
            'подстановочный процент' => ['%'],
            'процент внутри' => ['AB%CD'],
            'подчёркивание как джокер' => ['____'],
            'точка' => ['AB.CD'],
            'пробел' => ['AB CD'],
            'кавычка' => ['AB"CD'],
        ];
    }

    #[DataProvider('forbiddenKeys')]
    public function test_api_store_rejects_a_key_outside_the_allowed_format(string $key): void
    {
        $person = Person::factory()->create();

        $this->postJson('/api/v1/keys', [
            'key' => $key,
            'type' => 'Mifare',
            'person_id' => $person->id,
        ], $this->headers)->assertJsonValidationErrors(['key']);
    }

    public function test_api_store_accepts_letters_digits_and_dash(): void
    {
        $person = Person::factory()->create();

        $this->postJson('/api/v1/keys', [
            'key' => 'AB-12cd34',
            'type' => 'Mifare',
            'person_id' => $person->id,
        ], $this->headers)->assertStatus(201);
    }

    public function test_api_update_rejects_a_key_outside_the_allowed_format(): void
    {
        $key = Model::withoutEvents(fn () => Key::factory()->create(['key' => 'GOODKEY01']));

        $this->postJson("/api/v1/keys/{$key->id}", ['key' => '%'], $this->headers)
            ->assertJsonValidationErrors(['key']);

        $this->assertSame('GOODKEY01', $key->fresh()->key);
    }

    public function test_key_resource_rules_reject_a_wildcard(): void
    {
        $person = Model::withoutEvents(fn () => Person::factory()->create());

        $rules = app(KeyResource::class)->rules(null);

        $this->assertFalse(
            Validator::make(['key' => '%', 'type' => 'Mifare', 'person_id' => $person->id], $rules)->passes(),
        );
    }

    public function test_save_key_rejects_a_wildcard(): void
    {
        $person = Model::withoutEvents(fn () => Person::factory()->create());
        $user = User::factory()->create(['is_super' => true]);
        $resource = app(PersonResource::class);

        $this->actingAs($user, 'moonshine')
            ->postJson(
                $resource->getAsyncMethodUrl(
                    'saveKey',
                    params: ['item_id' => $person->id],
                    page: $resource->getIndexPage(),
                ),
                ['person_id' => $person->id, 'type' => 'Mifare', 'key' => '%'],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('key');

        $this->assertSame(0, Key::query()->count());
    }

    public function test_wildcard_key_no_longer_wipes_pending_commands_of_other_people(): void
    {
        $person = Model::withoutEvents(function () {
            $person = Person::factory()->create();
            Key::factory()->create(['key' => '%', 'person_id' => $person->id]);

            return $person;
        });

        $foreign = SkudCommand::create([
            'controller_id' => '1',
            'message' => json_encode(['operation' => 'add_cards', 'card' => 'AABBCCDD']),
            'execute_at' => now()->addHour(),
        ]);

        app(PersonObserver::class)->scheduleCommands($person);

        $this->assertNotNull($foreign->fresh(), 'Команда чужой карты не должна была удалиться');
    }
}
