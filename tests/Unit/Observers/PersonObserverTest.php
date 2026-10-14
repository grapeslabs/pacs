<?php

namespace Tests\Unit\Observers;

use App\Models\GrapeslabsSkudController;
use App\Models\Person;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use App\Observers\PersonObserver;
use App\Services\VideoAnalyticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeSkudDriver;
use Tests\TestCase;

class PersonObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeSkudDriver::reset();
        Config::set('pinvideo-skud.controller_types.pinterm.driver', FakeSkudDriver::class);
        Config::set('pinvideo-skud.controller_types.ironlogic.driver', FakeSkudDriver::class);

        Storage::fake('public');

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('personCreate')->andReturn([]);
            $mock->shouldReceive('personDelete')->andReturn([]);
        });
    }

    private function faceController(): GrapeslabsSkudController
    {
        return GrapeslabsSkudController::factory()->create(['type' => 'pinterm']);
    }

    public function test_creating_a_person_generates_key_uid_and_grapesva_uuid_when_missing(): void
    {
        $person = Person::create(['first_name' => 'Иван']);

        $this->assertSame('person_'.$person->id, $person->fresh()->key_uid);
        $this->assertNotNull($person->fresh()->grapesva_uuid);
    }

    public function test_creating_a_person_keeps_an_explicitly_provided_key_uid(): void
    {
        $person = Person::create(['first_name' => 'Иван', 'key_uid' => 'custom-uid']);

        $this->assertSame('custom-uid', $person->fresh()->key_uid);
    }

    public function test_creating_an_unfrozen_person_provisions_face_key_on_unmanaged_controllers(): void
    {
        $controller = $this->faceController();

        $person = Person::create(['first_name' => 'Иван']);

        $calls = FakeSkudDriver::callsFor($controller->id);
        $this->assertCount(1, $calls);
        $this->assertSame('write', $calls[0]['action']);
        $this->assertSame($person->fresh()->key_uid, $calls[0]['keys'][0]->uid);
    }

    public function test_creating_a_currently_frozen_person_skips_immediate_provisioning(): void
    {
        $this->faceController();

        Person::create([
            'first_name' => 'Иван',
            'frozen_start' => now()->subDay(),
            'frozen_end' => now()->addDay(),
        ]);

        $this->assertSame([], FakeSkudDriver::$calls);
    }

    public function test_creating_a_currently_frozen_person_schedules_unfreeze_command(): void
    {
        $controller = $this->faceController();
        $end = now()->addDay();

        $person = Person::create([
            'first_name' => 'Иван',
            'frozen_start' => now()->subDay(),
            'frozen_end' => $end,
        ]);

        $commands = SkudCommand::where('controller_id', $controller->id)->get();
        $this->assertCount(1, $commands);

        $command = $commands->first();
        $payload = json_decode($command->message, true);

        $this->assertSame('add_persons', $payload['operation']);
        $this->assertSame($person->fresh()->key_uid, $payload['persons'][0]['person']);
        $this->assertEqualsWithDelta($end->timestamp, Carbon::parse($command->execute_at)->timestamp, 1);
    }

    public function test_creating_a_person_with_future_freeze_provisions_now_and_schedules_defreeze(): void
    {
        $controller = $this->faceController();
        $start = now()->addDay();
        $end = now()->addWeek();

        $person = Person::create([
            'first_name' => 'Иван',
            'frozen_start' => $start,
            'frozen_end' => $end,
        ]);

        $immediate = FakeSkudDriver::callsFor($controller->id);
        $this->assertCount(1, $immediate);
        $this->assertSame('write', $immediate[0]['action']);

        $commands = SkudCommand::where('controller_id', $controller->id)
            ->orderBy('execute_at')
            ->get();

        $this->assertSame(
            ['del_persons', 'add_persons'],
            $commands->map(fn ($cmd) => json_decode($cmd->message, true)['operation'])->all()
        );
        $this->assertEqualsWithDelta($start->timestamp, Carbon::parse($commands[0]->execute_at)->timestamp, 1);
        $this->assertEqualsWithDelta($end->timestamp, Carbon::parse($commands[1]->execute_at)->timestamp, 1);
    }

    public function test_person_is_not_considered_frozen_exactly_at_frozen_end(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-06-01 12:00:00'));

        try {
            $observer = $this->app->make(PersonObserver::class);
            $this->assertFalse($observer->isCurrentlyFrozen($now->copy()->subHour(), $now));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_freezing_an_active_person_queues_delete_commands_without_touching_drivers(): void
    {
        $controller = $this->faceController();
        $person = Person::create(['first_name' => 'Иван']);
        FakeSkudDriver::reset();
        SkudCommand::query()->delete();

        $person->update(['frozen_start' => now()->subMinute(), 'frozen_end' => now()->addDay()]);

        $this->assertSame([], FakeSkudDriver::$calls, 'заморозка не должна дёргать драйвер напрямую');
        $operations = SkudCommand::where('controller_id', $controller->id)->get()
            ->map(fn ($c) => json_decode($c->message, true)['operation']);
        $this->assertContains('del_persons', $operations);
    }

    public function test_unfreezing_a_person_queues_add_commands_without_touching_drivers(): void
    {
        $controller = $this->faceController();
        $person = Person::create([
            'first_name' => 'Иван',
            'frozen_start' => now()->subDay(),
            'frozen_end' => now()->addDay(),
        ]);
        FakeSkudDriver::reset();
        SkudCommand::query()->delete();

        $person->update(['frozen_start' => now()->subDay(), 'frozen_end' => now()->subMinute()]);

        $this->assertSame([], FakeSkudDriver::$calls);
        $command = SkudCommand::where('controller_id', $controller->id)->sole();
        $this->assertSame('add_persons', json_decode($command->message, true)['operation']);
    }

    public function test_changing_photo_while_active_syncs_face_key_directly_with_driver(): void
    {
        $controller = $this->faceController();
        $person = Person::create(['first_name' => 'Иван']);
        FakeSkudDriver::reset();

        $person->update(['photo' => ['persons/photo.jpg']]);

        $calls = FakeSkudDriver::callsFor($controller->id);
        $this->assertSame('write', end($calls)['action']);
    }

    public function test_changing_photo_while_frozen_does_not_sync(): void
    {
        $controller = $this->faceController();
        $person = Person::create([
            'first_name' => 'Иван',
            'frozen_start' => now()->subDay(),
            'frozen_end' => now()->addDay(),
        ]);
        FakeSkudDriver::reset();

        $person->update(['photo' => ['persons/photo.jpg']]);

        $this->assertSame([], FakeSkudDriver::callsFor($controller->id));
    }

    public function test_deleting_a_person_soft_deletes_keys_without_driver_calls(): void
    {
        $this->faceController();
        $person = Person::create(['first_name' => 'Иван']);
        $key = \App\Models\Key::factory()->create(['person_id' => $person->id, 'key' => 'CARD-DEL']);
        FakeSkudDriver::reset();

        $person->delete();

        $this->assertSame([], FakeSkudDriver::$calls);
        $this->assertSoftDeleted('keys', ['id' => $key->id]);
    }

    public function test_deleting_a_person_queues_a_surviving_del_persons_command(): void
    {
        $controller = $this->faceController();
        $person = Person::create(['first_name' => 'Иван']);

        $person->delete();

        $command = SkudCommand::where('controller_id', $controller->id)->sole();
        $payload = json_decode($command->message, true);

        $this->assertSame('del_persons', $payload['operation']);
        $this->assertSame($person->getSkudUid(), $payload['persons'][0]['person']);
    }

    public function test_deleting_a_person_clears_its_stale_pending_commands(): void
    {
        $controller = $this->faceController();
        $person = Person::create(['first_name' => 'Иван']);
        $uid = $person->getSkudUid();

        SkudCommand::create([
            'controller_id' => $controller->id,
            'message' => json_encode(['operation' => 'add_persons', 'persons' => [['person' => $uid]]]),
            'execute_at' => now()->addWeek(),
        ]);

        $person->delete();

        $operations = SkudCommand::where('controller_id', $controller->id)
            ->get()
            ->map(fn ($cmd) => json_decode($cmd->message, true)['operation'])
            ->all();

        $this->assertSame(['del_persons'], $operations);
    }

    public function test_deleting_a_person_queues_del_cards_for_rfid_controllers(): void
    {
        $controller = GrapeslabsSkudController::factory()->create(['type' => 'z5rweb']);
        $person = Person::create(['first_name' => 'Иван']);
        \App\Models\Key::factory()->create(['person_id' => $person->id, 'key' => 'CARD-RFID']);
        SkudCommand::query()->delete();

        $person->delete();

        $command = SkudCommand::where('controller_id', $controller->id)->sole();
        $payload = json_decode($command->message, true);

        $this->assertSame('del_cards', $payload['operation']);
        $this->assertSame([['card' => 'CARD-RFID']], $payload['cards']);
    }

    public function test_deleting_a_freshly_loaded_person_also_queues_del_cards(): void
    {
        $controller = GrapeslabsSkudController::factory()->create(['type' => 'z5rweb']);
        $person = Person::create(['first_name' => 'Иван']);
        \App\Models\Key::factory()->create(['person_id' => $person->id, 'key' => 'CARD-RELOADED']);
        SkudCommand::query()->delete();

        Person::findOrFail($person->id)->delete();

        $payload = json_decode(SkudCommand::where('controller_id', $controller->id)->sole()->message, true);

        $this->assertSame([['card' => 'CARD-RELOADED']], $payload['cards']);
    }

    public function test_deleting_a_person_clears_pending_commands_mentioning_its_cards(): void
    {
        $controller = GrapeslabsSkudController::factory()->create(['type' => 'z5rweb']);
        $person = Person::create(['first_name' => 'Иван']);
        \App\Models\Key::factory()->create(['person_id' => $person->id, 'key' => 'CARD-STALE']);
        SkudCommand::query()->delete();

        SkudCommand::create([
            'controller_id' => $controller->id,
            'message' => json_encode(['operation' => 'add_cards', 'cards' => [['card' => 'CARD-STALE']]]),
            'execute_at' => now()->addWeek(),
        ]);

        $person->delete();

        $operations = SkudCommand::where('controller_id', $controller->id)
            ->get()
            ->map(fn ($cmd) => json_decode($cmd->message, true)['operation'])
            ->all();

        $this->assertSame(['del_cards'], $operations);
    }

    public function test_deleting_a_person_calls_analytic_service_person_delete(): void
    {
        $person = Person::create(['first_name' => 'Иван', 'grapesva_uuid' => 'uuid-123']);

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('personDelete')->once()->with('uuid-123')->andReturn([]);
        });

        $person->delete();
    }

    public function test_schedule_commands_purges_stale_future_commands_for_unfrozen_person(): void
    {
        $person = Person::create(['first_name' => 'Иван']);
        $stale = SkudCommand::create([
            'controller_id' => 1,
            'execute_at' => now()->addHour(),
            'message' => json_encode(['person' => $person->key_uid]),
        ]);

        $this->app->make(PersonObserver::class)->scheduleCommands($person->fresh());

        $this->assertDatabaseMissing('grapeslabs_skud_commands', ['id' => $stale->id]);
    }
}
