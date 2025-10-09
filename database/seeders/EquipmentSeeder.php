<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\Person;

class EquipmentSeeder extends Seeder
{
    const EQUIPMENT_COUNT = 25;

    public function run(): void
    {
        $people = Person::all();

        // Создаем контроллеры Ironlogic
        Equipment::factory()
            ->ironlogic()
            ->withSkudController()
            ->count(3)
            ->sequence(
                ['name' => 'Ironlogic Main Controller', 'description' => 'Основной контроллер'],
                ['name' => 'Ironlogic Building A', 'description' => 'Контроллер корпуса А'],
                ['name' => 'Ironlogic Building B', 'description' => 'Контроллер корпуса Б'],
            )
            ->create();

        // Создаем контроллеры Z5R
        Equipment::factory()
            ->z5rweb()
            ->withSkudController()
            ->count(2)
            ->sequence(
                ['name' => 'Z5R Gate Controller', 'description' => 'Контроллер шлагбаума'],
                ['name' => 'Z5R Parking Controller', 'description' => 'Контроллер парковки'],
            )
            ->create();

        // Контроллер на обслуживании
        Equipment::factory()
            ->ironlogic()
            ->withSkudController()
            ->create([
                'name' => 'Ironlogic Backup',
                'description' => 'Резервный контроллер, ожидает замены блока питания',
            ]);

        // Терминалы доступа (pinterm)
        Equipment::factory()
            ->pinterm()
            ->withSkudController()
            ->count(8)
            ->create();

        // Шлагбаумы (pingate)
        Equipment::factory()
            ->pingate()
            ->withSkudController()
            ->count(4)
            ->create();

        // Терминалы на обслуживании
        Equipment::factory()
            ->pinterm()
            ->withSkudController()
            ->count(2)
            ->create();

        // Шлагбаум на обслуживании
        Equipment::factory()
            ->pingate()
            ->withSkudController()
            ->count(1)
            ->create();

        // Специальные терминалы с привязкой к людям
        if ($people->isNotEmpty()) {
            $selectedPeople = $people->random(min(3, $people->count()));

            foreach ($selectedPeople as $person) {
                Equipment::factory()
                    ->pinterm()
                    ->withSkudController()
                    ->withPerson($person->getSkudUid(), $person->getFullName())
                    ->create([
                        'name' => 'Терминал ' . $person->getFullName(),
                        'description' => 'Персональный терминал в кабинете ' . $person->last_name,
                    ]);
            }
        }

        // Оборудование со специальными серийными номерами контроллеров
        Equipment::factory()
            ->pingate()
            ->withSkudController()
            ->create([
                'name' => 'Главный шлагбаум',
                'description' => 'Центральный въезд на территорию',
            ]);

        Equipment::factory()
            ->pinterm()
            ->withSkudController()
            ->create([
                'name' => 'Терминал директора',
                'description' => 'Терминал в приемной',
            ]);

        $total = Equipment::count();
        $this->command->info("✅ Создано оборудования: {$total}");
    }
}
