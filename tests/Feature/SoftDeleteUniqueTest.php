<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Key;
use App\Models\Person;
use App\Models\Storage;
use App\Models\Stream;
use App\Models\User;
use App\Services\VideoAnalyticLprService;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\PersonResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SoftDeleteUniqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_deleted_car_plate_can_be_reused_at_the_database_level(): void
    {
        Model::withoutEvents(function () {
            $car = Car::factory()->create(['license_plate' => 'А123ВС777']);
            $car->delete();

            Car::factory()->create(['license_plate' => 'А123ВС777']);
        });

        $this->assertSame(1, Car::query()->where('license_plate', 'А123ВС777')->count());
        $this->assertSame(2, Car::withTrashed()->where('license_plate', 'А123ВС777')->count());
    }

    public function test_deleted_key_can_be_reused_at_the_database_level(): void
    {
        Model::withoutEvents(function () {
            $person = Person::factory()->create();

            $key = Key::factory()->create(['key' => 'AABBCCDD', 'person_id' => $person->id]);
            $key->delete();

            Key::factory()->create(['key' => 'AABBCCDD', 'person_id' => $person->id]);
        });

        $this->assertSame(1, Key::query()->where('key', 'AABBCCDD')->count());
        $this->assertSame(2, Key::withTrashed()->where('key', 'AABBCCDD')->count());
    }

    public function test_car_resource_rules_ignore_soft_deleted_plates(): void
    {
        Model::withoutEvents(function () {
            Car::factory()->create(['license_plate' => 'В777ЕЕ99'])->delete();
        });

        $rules = app(CarResource::class)->rules(null);

        $this->assertTrue(
            Validator::make(['license_plate' => 'В777ЕЕ99'], ['license_plate' => $rules['license_plate']])->passes(),
        );
    }

    public function test_car_resource_rules_still_reject_an_active_plate(): void
    {
        Model::withoutEvents(function () {
            Car::factory()->create(['license_plate' => 'С555НН50']);
        });

        $rules = app(CarResource::class)->rules(null);

        $this->assertFalse(
            Validator::make(['license_plate' => 'С555НН50'], ['license_plate' => $rules['license_plate']])->passes(),
        );
    }

    public function test_key_resource_rules_ignore_soft_deleted_keys_but_reject_active_ones(): void
    {
        [$person] = Model::withoutEvents(function () {
            $person = Person::factory()->create();

            Key::factory()->create(['key' => 'DEADBEEF', 'person_id' => $person->id])->delete();
            Key::factory()->create(['key' => 'CAFEBABE', 'person_id' => $person->id]);

            return [$person];
        });

        $rules = app(KeyResource::class)->rules(null);

        $this->assertTrue(
            Validator::make(
                ['key' => 'DEADBEEF', 'type' => 'Mifare', 'person_id' => $person->id],
                $rules,
            )->passes(),
        );

        $this->assertFalse(
            Validator::make(
                ['key' => 'CAFEBABE', 'type' => 'Mifare', 'person_id' => $person->id],
                $rules,
            )->passes(),
        );
    }

    public function test_save_key_rejects_a_duplicate_of_an_active_key(): void
    {
        $person = Model::withoutEvents(function () {
            $person = Person::factory()->create();
            Key::factory()->create(['key' => 'FEEDFACE', 'person_id' => $person->id]);

            return $person;
        });

        $user = User::factory()->create(['is_super' => true]);

        $resource = app(PersonResource::class);

        $this->actingAs($user, 'moonshine')
            ->postJson(
                $resource->getAsyncMethodUrl(
                    'saveKey',
                    params: ['item_id' => $person->id],
                    page: $resource->getIndexPage(),
                ),
                ['person_id' => $person->id, 'type' => 'Mifare', 'key' => 'FEEDFACE'],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('key');

        $this->assertSame(1, Key::query()->where('key', 'FEEDFACE')->count());
    }

    public function test_auto_collect_restores_a_soft_deleted_car(): void
    {
        $car = Model::withoutEvents(function () {
            $car = Car::factory()->create(['license_plate' => 'Х001АА77']);
            $car->delete();

            return $car;
        });

        $stream = Model::withoutEvents(function () {
            $storage = Storage::create(['name' => 'Test storage', 'type' => 'ftp', 'data' => []]);

            return Stream::query()->create([
                'uid' => 'cam-restore',
                'storage_id' => $storage->id,
                'name' => 'Камера',
                'rtsp' => 'rtsp://127.0.0.1/stream',
                'archive_time' => 24,
                'is_active' => true,
                'va_options' => ['is_plate_autocollect' => true],
            ]);
        });

        Config::set('services.lpr.enabled', true);

        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('carCreate')->once()->andReturn(['ok' => true]);
        });

        $command = new \App\Console\Commands\ProcessCarPassageRules();
        $method = new \ReflectionMethod($command, 'autoCollectCar');
        $method->setAccessible(true);

        $restored = $method->invoke($command, $stream, 'Х001АА77');

        $this->assertNotNull($restored);
        $this->assertSame($car->id, $restored->id);
        $this->assertNull($restored->fresh()->deleted_at);
        $this->assertSame(1, Car::withTrashed()->where('license_plate', 'Х001АА77')->count());
    }
}
