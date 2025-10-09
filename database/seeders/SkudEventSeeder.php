<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use GrapesLabs\PinvideoSkud\Models\SkudEvent;
use Database\Factories\SkudEventFactory; // Добавить импорт
use App\Models\Person;
use App\Models\Car;

class SkudEventSeeder extends Seeder
{
    const EVENTS_COUNT = 500;

    public function run(): void
    {
        $controllers = SkudController::all();
        if ($controllers->isEmpty()) {
            $this->command->warn('⚠️ Нет контроллеров СКУД. События не созданы.');
            return;
        }

        $people = Person::all();
        $cars = Car::all();

        $this->command->info('📊 Создание событий СКУД...');

        $factory = SkudEventFactory::new(); // Создаем фабрику напрямую

        $bar = $this->command->getOutput()->createProgressBar(self::EVENTS_COUNT);
        $bar->start();

        for ($i = 0; $i < self::EVENTS_COUNT; $i++) {
            $this->createRandomEvent($factory, $controllers, $people, $cars);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->showStatistics();
    }

    private function createRandomEvent($factory, $controllers, $people, $cars): void
    {
        $controller = $controllers->random();

        $useExistingData = fake()->boolean(70);

        switch ($controller->type) {
            case 'pingate':
                if ($useExistingData && $cars->isNotEmpty()) {
                    $car = $cars->random();
                    $factory->forCar($car)->create(['controller_id' => $controller->id]);
                } else {
                    $factory->forUnknownCar()->create(['controller_id' => $controller->id]);
                }
                break;

            default:
                if ($useExistingData && $people->isNotEmpty()) {
                    $person = $people->random();
                    $factory->forPerson($person)->create(['controller_id' => $controller->id]);
                } else {
                    $factory->forUnknownPerson()->create(['controller_id' => $controller->id]);
                }
                break;
        }
    }

    private function showStatistics(): void
    {
        $total = SkudEvent::count();
        $this->command->info("✅ Всего создано событий: {$total}");
    }
}
