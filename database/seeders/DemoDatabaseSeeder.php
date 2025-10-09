<?php
// database/seeders/DemoDatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    const ORGANIZATIONS_COUNT = 15;
    const PERSONS_COUNT = 50;
    const BOTS_COUNT = 5;

    public function run(): void
    {
        $this->command->info('🚀 Начинаем наполнение базы демо-данными...');

        // Сначала независимые таблицы
        $this->command->info('📋 Шаг 1/10: Создание тегов...');
        $this->callWith(TagSeeder::class, ['command' => $this->command]);

        $this->command->info('📋 Шаг 2/10: Создание марок автомобилей...');
        $this->callWith(CarBrandSeeder::class, ['command' => $this->command]);

        $this->command->info('📋 Шаг 3/10: Создание цветов автомобилей...');
        $this->callWith(CarColorSeeder::class, ['command' => $this->command]);

        // Организации
        $this->command->info('📋 Шаг 4/10: Создание организаций...');
        $this->callWith(OrganizationSeeder::class, [
            'count' => self::ORGANIZATIONS_COUNT,
            'command' => $this->command
        ]);

        // Персоны
        $this->command->info('📋 Шаг 5/10: Создание персон...');
        $this->callWith(PersonSeeder::class, [
            'count' => self::PERSONS_COUNT,
            'command' => $this->command
        ]);

        // Автомобили
        $this->command->info('📋 Шаг 6/10: Создание автомобилей...');
        $this->callWith(CarSeeder::class, ['command' => $this->command]);

        // Оборудование СКУД
        $this->command->info('📋 Шаг 7/10: Создание оборудования СКУД...');
        $this->callWith(EquipmentSeeder::class, ['command' => $this->command]);

        // Боты
        $this->command->info('📋 Шаг 8/10: Создание ботов...');
        $this->callWith(BotSeeder::class, [
            'count' => self::BOTS_COUNT,
            'command' => $this->command
        ]);

        // Триггеры
        $this->command->info('📋 Шаг 9/10: Создание триггеров...');
        $this->callWith(TriggerSeeder::class, ['command' => $this->command]);

        // Api
        $this->command->info('📋 Шаг 10/10: Создание API ключей...');
        $this->callWith(ApiKeySeeder::class, ['command' => $this->command]);

        // События СКУД (после создания всех данных)
        $this->command->info('📋 Завершение: Создание событий СКУД...');
        $this->callWith(SkudEventSeeder::class, ['command' => $this->command]);

        $this->command->info('✅ Все демо-данные успешно созданы!');

        // Показываем статистику
        $this->showStatistics();
    }

    /**
     * Показать статистику созданных записей
     */
    private function showStatistics(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Статистика демо-данных:');

        $tables = [
            'Организации' => \App\Models\Organization::count(),
            'Теги' => \App\Models\Tag::count(),
            'Персоны' => \App\Models\Person::count(),
            'Связи персон с тегами' => \DB::table('person_tag')->count(),
            'Марки авто' => \App\Models\CarBrand::count(),
            'Цвета авто' => \App\Models\CarColor::count(),
            'Автомобили' => \App\Models\Car::count(),
            'Связи авто с людьми' => \DB::table('car_person')->count(),
            'Оборудование СКУД' => \App\Models\Equipment::count(),
            'Контроллеры СКУД' => \GrapesLabs\PinvideoSkud\Models\SkudController::count(),
            'Боты' => \App\Models\Bot::count(),
            'Триггеры' => \App\Models\Trigger::count(),
            'Api-ключи' => \App\Models\ApiKey::count(),
            'События СКУД' => \GrapesLabs\PinvideoSkud\Models\SkudEvent::count(),
        ];

        foreach ($tables as $name => $count) {
            $this->command->line("   {$name}: {$count}");
        }
        $this->command->newLine();
    }
}
