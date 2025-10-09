<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarColor;
use App\Models\Organization;
use App\Models\Person;

class CarSeeder extends Seeder
{
    const CARS_COUNT = 30;

    public function run(): void
    {
        $organizations = Organization::all();
        $people = Person::all();
        $brands = CarBrand::all();
        $colors = CarColor::all();

        if ($brands->isEmpty() || $colors->isEmpty()) {
            $this->command->warn('⚠️ Нет марок или цветов, сначала выполните CarBrandSeeder и CarColorSeeder');
            return;
        }

        // Создаем машины
        for ($i = 0; $i < self::CARS_COUNT; $i++) {
            $organization = $organizations->isNotEmpty() && fake()->boolean(60)
                ? $organizations->random()
                : null;

            $car = Car::factory()
                ->when($organization, fn($factory) => $factory->forOrganization($organization))
                ->create();

            // Привязываем случайных людей (1-3 владельца на машину)
            if ($people->isNotEmpty()) {
                $carPeople = $people->random(fake()->numberBetween(1, min(3, $people->count())));
                $car->people()->attach($carPeople->pluck('id'));
            }
        }

        // Создаем специальные машины
        $this->createSpecialCars($organizations, $people);

        $total = Car::count();
        $this->command->info("✅ Создано автомобилей: {$total}");
    }

    /**
     * Создать специальные машины
     */
    private function createSpecialCars($organizations, $people): void
    {
        // Машина Иванова Ивана (если есть)
        $ivanov = $people->where('last_name', 'Иванов')->where('first_name', 'Иван')->first();
        if ($ivanov) {
            $car = Car::factory()
                ->withPlate('А001АА 777')
                ->create([
                    'comment' => 'Личный автомобиль Иванова',
                ]);
            $car->people()->attach($ivanov->id);
        }

        // Машина с военным номером
        $militaryCar = Car::factory()
            ->military()
            ->create([
                'comment' => 'Автомобиль с военными номерами',
            ]);

        // Привязываем случайного человека
        if ($people->isNotEmpty()) {
            $militaryCar->people()->attach($people->random()->id);
        }

        // Машина без владельца (для тестирования)
        Car::factory()
            ->withPlate('В234СВ 199')
            ->create([
                'comment' => 'Автомобиль без привязки к людям',
            ]);
    }
}
