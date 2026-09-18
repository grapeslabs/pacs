<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\SkudControllerSeeder;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkudControllerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_type_mix_the_demo_data_relies_on(): void
    {
        $this->seed(SkudControllerSeeder::class);

        $byType = SkudController::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($total) => (int) $total)
            ->all();

        $expected = SkudControllerSeeder::COUNTS;
        ksort($expected);
        ksort($byType);

        $this->assertSame($expected, $byType);
        $this->assertSame(
            array_sum(SkudControllerSeeder::COUNTS),
            SkudController::query()->distinct()->count('serial_number'),
        );
    }
}
