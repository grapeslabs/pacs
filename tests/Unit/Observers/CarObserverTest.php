<?php

namespace Tests\Unit\Observers;

use App\Models\Car;
use App\Models\CarPassageEvent;
use App\Services\VideoAnalyticLprService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CarObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.lpr.enabled', false);
    }

    private function unlinkedEvent(string $plateText): CarPassageEvent
    {
        return CarPassageEvent::create(['plate_text' => $plateText, 'car_id' => null]);
    }

    public function test_creating_links_passage_events_with_exact_plate_match(): void
    {
        $event = $this->unlinkedEvent('A123BC777');

        $car = Car::factory()->create(['license_plate' => 'A123BC777']);

        $this->assertSame($car->id, $event->fresh()->car_id);
    }

    public function test_creating_links_when_stored_plate_contains_a_space(): void
    {
        $event = $this->unlinkedEvent('A123BC777');

        $car = Car::factory()->create(['license_plate' => 'A123BC 777']);

        $this->assertSame($car->id, $event->fresh()->car_id);
    }

    public function test_creating_links_when_case_differs(): void
    {
        $event = $this->unlinkedEvent('A123BC777');

        $car = Car::factory()->create(['license_plate' => 'a123bc777']);

        $this->assertSame($car->id, $event->fresh()->car_id);
    }

    public function test_creating_links_cyrillic_plate_written_in_a_human_readable_form(): void
    {
        $event = $this->unlinkedEvent('А123ВС777');

        $car = Car::factory()->create(['license_plate' => 'а123вс 777']);

        $this->assertSame($car->id, $event->fresh()->car_id);
    }

    public function test_creating_calls_lpr_car_create_when_lpr_enabled(): void
    {
        Config::set('services.lpr.enabled', true);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('carCreate')->once()
                ->with('A123BC777', 'note')
                ->andReturn(['ok' => true]);
        });

        Car::factory()->create(['license_plate' => 'A123BC777', 'comment' => 'note']);
    }

    public function test_creating_skips_lpr_when_disabled(): void
    {
        $this->mock(VideoAnalyticLprService::class);

        Car::factory()->create(['license_plate' => 'A123BC777']);

        $this->addToAssertionCount(1);
    }

    public function test_deleting_calls_lpr_car_delete_when_enabled(): void
    {
        $car = Car::factory()->create(['license_plate' => 'A123BC777']);

        Config::set('services.lpr.enabled', true);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('carDelete')->once()->with('A123BC777')->andReturn(['ok' => true]);
        });

        $car->delete();
    }

    public function test_restoring_recreates_in_lpr_when_enabled(): void
    {
        $car = Car::factory()->create(['license_plate' => 'A123BC777', 'comment' => null]);
        $car->delete();

        Config::set('services.lpr.enabled', true);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('carCreate')->once()->with('A123BC777', '')->andReturn(['ok' => true]);
        });

        $car->restore();
    }

    public function test_updating_plate_unlinks_old_events_and_links_new_matches(): void
    {
        $car = Car::factory()->create(['license_plate' => 'OLD111']);
        $stillOld = $this->unlinkedEvent('OLD111');
        $stillOld->update(['car_id' => $car->id]);
        $newMatch = $this->unlinkedEvent('NEW222');

        $car->update(['license_plate' => 'NEW222']);

        $this->assertNull($stillOld->fresh()->car_id);
        $this->assertSame($car->id, $newMatch->fresh()->car_id);
    }

    public function test_updating_plate_forces_lpr_delete_then_recreate(): void
    {
        $car = Car::factory()->create(['license_plate' => 'OLD111', 'comment' => 'c']);

        Config::set('services.lpr.enabled', true);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('carDelete')->once()->with('OLD111')->andReturn(['ok' => true]);
            $mock->shouldReceive('carCreate')->once()->with('NEW222', 'c')->andReturn(['ok' => true]);
        });

        $car->update(['license_plate' => 'NEW222']);
    }

    public function test_updating_only_the_comment_still_forces_lpr_delete_and_recreate(): void
    {
        $car = Car::factory()->create(['license_plate' => 'SAME1', 'comment' => 'old comment']);

        Config::set('services.lpr.enabled', true);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('carDelete')->once()->with('SAME1')->andReturn(['ok' => true]);
            $mock->shouldReceive('carCreate')->once()->with('SAME1', 'new comment')->andReturn(['ok' => true]);
        });

        $car->update(['comment' => 'new comment']);
    }

    public function test_updating_an_unrelated_field_does_not_touch_lpr(): void
    {
        $car = Car::factory()->create(['license_plate' => 'SAME1']);

        Config::set('services.lpr.enabled', true);
        $this->mock(VideoAnalyticLprService::class);

        $car->update(['source' => Car::SOURCE_MANUAL]);

        $this->addToAssertionCount(1);
    }
}
