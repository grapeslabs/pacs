<?php

namespace Tests\Unit\Observers;

use App\Models\Car;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SkudControllerObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.lpr.enabled', false);
    }

    private function controller(string $type = 'pingate'): SkudController
    {
        return SkudController::create([
            'serial_number' => 'SN-'.uniqid(),
            'type' => $type,
            'ip' => '10.0.0.1',
        ]);
    }

    private function commandsFor(SkudController $controller): array
    {
        return SkudCommand::where('controller_id', (string) $controller->id)
            ->orderBy('id')
            ->get()
            ->map(fn (SkudCommand $cmd) => json_decode($cmd->message, true) + ['_row_id' => $cmd->id])
            ->all();
    }

    private function operations(SkudController $controller): array
    {
        return array_column($this->commandsFor($controller), 'operation');
    }

    public function test_creating_pingate_controller_queues_set_active_then_clear_then_add_cars(): void
    {
        Car::factory()->create(['license_plate' => 'A111AA777']);

        $controller = $this->controller('pingate');

        $this->assertSame(['set_active', 'clear_cars', 'add_cars'], $this->operations($controller));
    }

    public function test_controller_type_is_matched_case_insensitively(): void
    {
        Car::factory()->create(['license_plate' => 'A222AA777']);

        $controller = $this->controller('PinGate');

        $this->assertSame(['set_active', 'clear_cars', 'add_cars'], $this->operations($controller));
    }

    public function test_creating_non_pingate_controller_queues_nothing(): void
    {
        Car::factory()->create(['license_plate' => 'A333AA777']);

        $controller = $this->controller('pinterm');

        $this->assertSame([], $this->operations($controller));
    }

    public function test_set_active_command_carries_active_flag_and_its_own_row_id(): void
    {
        $controller = $this->controller('pingate');

        $setActive = $this->commandsFor($controller)[0];

        $this->assertSame('set_active', $setActive['operation']);
        $this->assertSame(1, $setActive['active']);
        $this->assertSame($setActive['_row_id'], $setActive['id']);
    }

    public function test_add_cars_payload_wraps_every_plate_into_a_car_object(): void
    {
        Car::factory()->create(['license_plate' => 'A123BC777']);
        Car::factory()->create(['license_plate' => 'B456DE199']);

        $controller = $this->controller('pingate');

        $addCars = $this->commandsFor($controller)[2];
        $plates = array_column($addCars['cars'], 'car');
        sort($plates);

        $this->assertSame(['A123BC777', 'B456DE199'], $plates);
    }

    public function test_add_cars_is_skipped_entirely_when_there_are_no_plates(): void
    {
        $controller = $this->controller('pingate');

        $this->assertSame(['set_active', 'clear_cars'], $this->operations($controller));
    }

    public function test_cars_with_an_empty_plate_are_excluded(): void
    {
        Car::factory()->create(['license_plate' => '']);
        Car::factory()->create(['license_plate' => 'A777AA777']);

        $controller = $this->controller('pingate');

        $addCars = $this->commandsFor($controller)[2];

        $this->assertSame([['car' => 'A777AA777']], $addCars['cars']);
    }

    public function test_soft_deleted_cars_are_not_sent_to_a_new_controller(): void
    {
        $removed = Car::factory()->create(['license_plate' => 'DEL111']);
        Car::factory()->create(['license_plate' => 'KEEP11']);
        $removed->delete();

        $controller = $this->controller('pingate');

        $addCars = $this->commandsFor($controller)[2];

        $this->assertSame([['car' => 'KEEP11']], $addCars['cars']);
    }

    public function test_plates_are_sent_verbatim_including_spaces(): void
    {
        Car::factory()->create(['license_plate' => 'А123ВС 777']);

        $controller = $this->controller('pingate');

        $addCars = $this->commandsFor($controller)[2];

        $this->assertSame([['car' => 'А123ВС 777']], $addCars['cars']);
    }

    public function test_deleting_a_controller_removes_its_pending_commands(): void
    {
        Car::factory()->create(['license_plate' => 'A123BC777']);
        $controller = $this->controller('pingate');
        $this->assertNotEmpty($this->operations($controller));

        $controller->delete();

        $this->assertDatabaseMissing('grapeslabs_skud_controllers', ['id' => $controller->id]);
        $this->assertSame([], $this->operations($controller));
    }

    public function test_deleting_a_controller_leaves_other_controllers_queues_alone(): void
    {
        Car::factory()->create(['license_plate' => 'A123BC777']);
        $kept = $this->controller('pingate');
        $removed = $this->controller('pingate');

        $removed->delete();

        $this->assertSame(['set_active', 'clear_cars', 'add_cars'], $this->operations($kept));
    }

    public function test_reregistering_a_deleted_controller_clears_its_stale_whitelist(): void
    {
        Car::factory()->create(['license_plate' => 'A123BC777']);
        $first = $this->controller('pingate');
        $serial = $first->serial_number;
        $oldId = $first->id;

        $first->delete();

        $second = SkudController::create([
            'serial_number' => $serial,
            'type' => 'pingate',
            'ip' => '10.0.0.1',
        ]);

        $this->assertNotSame($oldId, $second->id);
        $this->assertSame([], $this->operations($first));
        $this->assertSame(['set_active', 'clear_cars', 'add_cars'], $this->operations($second));
    }

    public function test_deleting_a_non_pingate_controller_also_clears_its_queue(): void
    {
        $controller = $this->controller('z5rweb');
        SkudCommand::create([
            'controller_id' => (string) $controller->id,
            'message' => json_encode(['operation' => 'add_persons']),
        ]);

        $controller->delete();

        $this->assertSame([], $this->operations($controller));
    }

    public function test_observer_does_not_fire_for_the_app_level_twin_model(): void
    {
        Car::factory()->create(['license_plate' => 'A999AA777']);

        $twin = \App\Models\GrapeslabsSkudController::create([
            'serial_number' => 'SN-'.uniqid(),
            'type' => 'pingate',
            'ip' => '10.0.0.2',
        ]);

        $this->assertSame(
            0,
            SkudCommand::where('controller_id', (string) $twin->id)->count()
        );
    }
}
