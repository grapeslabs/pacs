<?php

namespace Tests\Unit\Observers;

use App\Models\GrapeslabsSkudController;
use App\Models\SkudEventCarPlate;
use App\Models\SkudEventPerson;
use GrapesLabs\PinvideoSkud\Models\SkudEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkudEventObserverTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(array $payload): SkudEvent
    {
        $controller = GrapeslabsSkudController::factory()->create();

        return SkudEvent::create([
            'datetime' => now(),
            'event_id' => 'evt-'.uniqid(),
            'controller_id' => $controller->id,
            'type' => 'access',
            'event' => json_encode($payload),
        ]);
    }

    public function test_creating_with_car_plate_creates_plate_record(): void
    {
        $event = $this->makeEvent(['car_plate' => 'A123BC']);

        $this->assertDatabaseHas('skud_event_car_plates', [
            'event_id' => $event->id,
            'car_plate' => 'A123BC',
        ]);
    }

    public function test_creating_with_card_number_creates_person_record(): void
    {
        $event = $this->makeEvent(['card_number' => 'CARD-1']);

        $this->assertDatabaseHas('skud_event_persons', [
            'event_id' => $event->id,
            'card_number' => 'CARD-1',
        ]);
    }

    public function test_creating_with_neither_field_creates_no_side_records(): void
    {
        $event = $this->makeEvent(['some_other_field' => 'x']);

        $this->assertSame(0, SkudEventCarPlate::where('event_id', $event->id)->count());
        $this->assertSame(0, SkudEventPerson::where('event_id', $event->id)->count());
    }

    public function test_updating_replaces_the_plate_value_in_place(): void
    {
        $event = $this->makeEvent(['car_plate' => 'OLD111']);

        $event->update(['event' => json_encode(['car_plate' => 'NEW222'])]);

        $this->assertSame(1, SkudEventCarPlate::where('event_id', $event->id)->count());
        $this->assertDatabaseHas('skud_event_car_plates', ['event_id' => $event->id, 'car_plate' => 'NEW222']);
        $this->assertDatabaseMissing('skud_event_car_plates', ['event_id' => $event->id, 'car_plate' => 'OLD111']);
    }

    public function test_updating_to_remove_the_plate_deletes_the_side_record(): void
    {
        $event = $this->makeEvent(['car_plate' => 'A123BC']);

        $event->update(['event' => json_encode(['note' => 'no plate anymore'])]);

        $this->assertSame(0, SkudEventCarPlate::where('event_id', $event->id)->count());
    }

    public function test_deleting_removes_both_side_records(): void
    {
        $event = $this->makeEvent(['car_plate' => 'A123BC', 'card_number' => 'CARD-1']);

        $event->delete();

        $this->assertSame(0, SkudEventCarPlate::where('event_id', $event->id)->count());
        $this->assertSame(0, SkudEventPerson::where('event_id', $event->id)->count());
    }
}
