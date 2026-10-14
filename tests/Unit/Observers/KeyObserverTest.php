<?php

namespace Tests\Unit\Observers;

use App\Models\GrapeslabsSkudController;
use App\Models\Key;
use App\Models\Person;
use App\Models\PersonPassageRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Support\FakeSkudDriver;
use Tests\TestCase;

class KeyObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeSkudDriver::reset();
        Config::set('pinvideo-skud.controller_types.ironlogic.driver', FakeSkudDriver::class);
    }

    private function rfidController(): GrapeslabsSkudController
    {
        return GrapeslabsSkudController::factory()->create(['type' => 'ironlogic']);
    }

    private function rule(string $type, array $controllers, array $keys = [], array $people = []): PersonPassageRule
    {
        $rule = PersonPassageRule::create(['name' => 'r-'.uniqid(), 'type' => $type, 'is_active' => true]);
        $rule->controllers()->attach(array_map(fn ($c) => $c->id, $controllers));
        $rule->keys()->attach(array_map(fn ($k) => $k->id, $keys));
        $rule->people()->attach(array_map(fn ($p) => $p->id, $people));

        return $rule;
    }

    public function test_creating_an_active_key_writes_it_when_no_rules_govern_the_controller(): void
    {
        $controller = $this->rfidController();
        $person = Person::factory()->create();

        $key = Key::factory()->create(['person_id' => $person->id, 'is_active' => true, 'key' => 'CARD-1']);

        $calls = FakeSkudDriver::callsFor($controller->id);
        $this->assertCount(1, $calls);
        $this->assertSame('write', $calls[0]['action']);
        $this->assertSame('CARD-1', $calls[0]['keys'][0]->code);
    }

    public function test_creating_a_key_denied_by_rule_clears_it_instead_of_writing(): void
    {
        $controller = $this->rfidController();
        $person = Person::factory()->create();
        $this->rule(PersonPassageRule::TYPE_DENY, [$controller], people: [$person]);

        Key::factory()->create(['person_id' => $person->id, 'is_active' => true, 'key' => 'CARD-2']);

        $calls = FakeSkudDriver::callsFor($controller->id);
        $this->assertCount(1, $calls);
        $this->assertSame('clear', $calls[0]['action']);
    }

    public function test_creating_an_inactive_key_never_gets_written_even_if_otherwise_allowed(): void
    {
        $controller = $this->rfidController();
        $person = Person::factory()->create();

        Key::factory()->create(['person_id' => $person->id, 'is_active' => false, 'key' => 'CARD-3']);

        $calls = FakeSkudDriver::callsFor($controller->id);
        $this->assertCount(1, $calls);
        $this->assertSame('clear', $calls[0]['action']);
    }

    public function test_changing_key_code_clears_the_old_code_and_writes_the_new_one(): void
    {
        $controller = $this->rfidController();
        $person = Person::factory()->create();
        $key = Key::factory()->create(['person_id' => $person->id, 'is_active' => true, 'key' => 'OLD-CODE']);
        FakeSkudDriver::reset();

        $key->update(['key' => 'NEW-CODE']);

        $calls = FakeSkudDriver::callsFor($controller->id);
        $this->assertCount(2, $calls);
        $this->assertSame('clear', $calls[0]['action']);
        $this->assertSame('OLD-CODE', $calls[0]['keys'][0]->code);
        $this->assertSame('write', $calls[1]['action']);
        $this->assertSame('NEW-CODE', $calls[1]['keys'][0]->code);
    }

    public function test_updating_an_unwatched_field_does_not_trigger_any_sync(): void
    {
        $controller = $this->rfidController();
        $key = Key::factory()->create(['is_active' => true, 'key' => 'CARD-4']);
        FakeSkudDriver::reset();

        $key->update(['issued_at' => now()->subDay()->toDateString()]);

        $this->assertSame([], FakeSkudDriver::callsFor($controller->id));
    }

    public function test_deleting_a_key_clears_it_from_every_rfid_controller(): void
    {
        $controllerA = $this->rfidController();
        $controllerB = $this->rfidController();
        $key = Key::factory()->create(['is_active' => true, 'key' => 'TO-DELETE']);
        FakeSkudDriver::reset();

        $key->delete();

        foreach ([$controllerA, $controllerB] as $controller) {
            $calls = FakeSkudDriver::callsFor($controller->id);
            $this->assertCount(1, $calls);
            $this->assertSame('clear', $calls[0]['action']);
            $this->assertSame('TO-DELETE', $calls[0]['keys'][0]->code);
        }
    }

    public function test_reconcile_person_keys_skips_sync_entirely_while_person_is_frozen(): void
    {
        $this->rfidController();
        $person = Person::factory()->create([
            'frozen_start' => now()->subDay(),
            'frozen_end' => now()->addDay(),
        ]);
        Key::factory()->create(['person_id' => $person->id, 'is_active' => true, 'key' => 'CARD-5']);
        FakeSkudDriver::reset();

        $this->app->make(\App\Observers\KeyObserver::class)->reconcilePersonKeys($person->fresh());

        $this->assertSame([], FakeSkudDriver::$calls);
    }
}
