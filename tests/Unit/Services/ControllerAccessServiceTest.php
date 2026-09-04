<?php

namespace Tests\Unit\Services;

use App\Models\GrapeslabsSkudController;
use App\Models\Key;
use App\Models\Person;
use App\Models\PersonPassageRule;
use App\Models\Tag;
use App\Services\ControllerAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ControllerAccessService
    {
        return $this->app->make(ControllerAccessService::class);
    }

    private function controller(string $type = 'ironlogic'): GrapeslabsSkudController
    {
        return GrapeslabsSkudController::factory()->create(['type' => $type]);
    }

    private function personWithKey(bool $keyActive = true, ?string $cardNumber = null): array
    {
        $person = Person::factory()->create();
        $key = Key::factory()->create([
            'person_id' => $person->id,
            'is_active' => $keyActive,
            'key' => $cardNumber ?? Key::factory()->make()->key,
        ]);

        return [$person, $key];
    }

    private function rule(
        string $type,
        array $controllers,
        array $keys = [],
        array $people = [],
        array $tags = [],
    ): PersonPassageRule {
        $rule = PersonPassageRule::create([
            'name' => 'rule-'.uniqid(),
            'type' => $type,
            'is_active' => true,
        ]);

        $rule->controllers()->attach(array_map(fn ($c) => $c->id, $controllers));
        $rule->keys()->attach(array_map(fn ($k) => $k->id, $keys));
        $rule->people()->attach(array_map(fn ($p) => $p->id, $people));
        $rule->personTags()->attach(array_map(fn ($t) => $t->id, $tags));

        return $rule;
    }

    public function test_controller_with_no_rules_is_open_to_everyone(): void
    {
        $unmanaged = $this->controller();
        [, $key] = $this->personWithKey();

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertTrue($result->contains('id', $unmanaged->id));
    }

    public function test_allow_rule_matching_by_explicit_key_grants_access(): void
    {
        $managed = $this->controller();
        [, $key] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$managed], keys: [$key]);

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertTrue($result->contains('id', $managed->id));
    }

    public function test_allow_rule_matching_by_person_grants_access(): void
    {
        $managed = $this->controller();
        [$person, $key] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$managed], people: [$person]);

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertTrue($result->contains('id', $managed->id));
    }

    public function test_allow_rule_matching_by_tag_intersection_grants_access(): void
    {
        $managed = $this->controller();
        [$person, $key] = $this->personWithKey();
        $tag = Tag::factory()->create();
        $person->tags()->attach($tag->id);
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$managed], tags: [$tag]);

        $result = $this->service()->rfidControllersForKey($key->fresh());

        $this->assertTrue($result->contains('id', $managed->id));
    }

    public function test_rule_without_any_subjects_matches_everyone(): void
    {
        $managed = $this->controller();
        [, $keyA] = $this->personWithKey();
        [, $keyB] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$managed]);

        $service = $this->service();

        $this->assertTrue($service->rfidControllersForKey($keyA)->contains('id', $managed->id));
        $this->assertTrue($service->rfidControllersForKey($keyB)->contains('id', $managed->id));
    }

    public function test_managed_controller_without_matching_allow_rule_is_denied(): void
    {
        $managed = $this->controller();
        [, $key] = $this->personWithKey();
        [$otherPerson] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$managed], people: [$otherPerson]);

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertFalse($result->contains('id', $managed->id));
    }

    public function test_deny_rule_matching_key_revokes_access(): void
    {
        $managed = $this->controller();
        [, $key] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_DENY, [$managed], keys: [$key]);

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertFalse($result->contains('id', $managed->id));
    }

    public function test_deny_rule_wins_over_matching_allow_rule(): void
    {
        $managed = $this->controller();
        [, $key] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$managed], keys: [$key]);
        $this->rule(PersonPassageRule::TYPE_DENY, [$managed], keys: [$key]);

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertFalse($result->contains('id', $managed->id));
    }

    public function test_rfid_resolution_ignores_face_and_gate_controller_types(): void
    {
        $rfid = $this->controller('ironlogic');
        $face = $this->controller('pinterm');
        $gate = $this->controller('pingate');
        [, $key] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$rfid, $face, $gate]);

        $result = $this->service()->rfidControllersForKey($key);

        $this->assertTrue($result->contains('id', $rfid->id));
        $this->assertFalse($result->contains('id', $face->id));
        $this->assertFalse($result->contains('id', $gate->id));
    }

    public function test_face_resolution_only_considers_pinterm_controllers(): void
    {
        $rfid = $this->controller('ironlogic');
        $face = $this->controller('pinterm');
        [$person] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$rfid, $face]);

        $result = $this->service()->faceControllersForPerson($person);

        $this->assertTrue($result->contains('id', $face->id));
        $this->assertFalse($result->contains('id', $rfid->id));
    }

    public function test_key_only_rule_never_grants_face_access(): void
    {
        $face = $this->controller('pinterm');
        [$person, $key] = $this->personWithKey();
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$face], keys: [$key]);

        $result = $this->service()->faceControllersForPerson($person);

        $this->assertFalse($result->contains('id', $face->id));
    }

    public function test_rfid_card_commands_are_grouped_per_controller_for_active_keys(): void
    {
        $a = $this->controller();
        $b = $this->controller();
        $person = Person::factory()->create();
        $key1 = Key::factory()->create(['person_id' => $person->id, 'is_active' => true, 'key' => 'CARD-1']);
        $key2 = Key::factory()->create(['person_id' => $person->id, 'is_active' => true, 'key' => 'CARD-2']);
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$a, $b], keys: [$key1]);
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$a], keys: [$key2]);

        $commands = $this->service()->rfidCardCommandsFor($person->fresh());

        $this->assertEqualsCanonicalizing(
            ['CARD-1', 'CARD-2'],
            array_column($commands[$a->id], 'card'),
        );
        $this->assertSame(['CARD-1'], array_column($commands[$b->id], 'card'));
        $this->assertSame(['card' => 'CARD-1', 'flags' => 0, 'tz' => 255], $commands[$a->id][0]);
    }

    public function test_rfid_card_commands_skip_inactive_keys(): void
    {
        $controller = $this->controller();
        $person = Person::factory()->create();
        $key = Key::factory()->create(['person_id' => $person->id, 'is_active' => false, 'key' => 'CARD-X']);
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$controller], keys: [$key]);

        $commands = $this->service()->rfidCardCommandsFor($person->fresh());

        $this->assertArrayNotHasKey($controller->id, $commands);
    }

    public function test_rfid_card_deletes_include_inactive_keys_unlike_commands(): void
    {
        $controller = $this->controller();
        $person = Person::factory()->create();
        $key = Key::factory()->create(['person_id' => $person->id, 'is_active' => false, 'key' => 'CARD-X']);
        $this->rule(PersonPassageRule::TYPE_ALLOW, [$controller], keys: [$key]);

        $deletes = $this->service()->rfidCardDeletesFor($person->fresh());

        $this->assertSame(['card' => 'CARD-X'], $deletes[$controller->id][0]);
    }

    public function test_flush_invalidates_the_rule_and_controller_cache(): void
    {
        $service = $this->service();
        [, $key] = $this->personWithKey();

        $before = $service->rfidControllersForKey($key)->count();

        $newController = $this->controller();

        $stale = $service->rfidControllersForKey($key)->count();
        $this->assertSame($before, $stale, 'до flush() новый контроллер не должен быть виден из кэша');

        $service->flush();

        $fresh = $service->rfidControllersForKey($key)->count();
        $this->assertSame($before + 1, $fresh);
    }
}
