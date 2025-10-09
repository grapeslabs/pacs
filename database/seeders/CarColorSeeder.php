<?php

namespace Database\Seeders;

use App\Models\CarColor;
use Illuminate\Database\Seeder;

class CarColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            'Белый', 'Черный', 'Серый', 'Серебристый', 'Синий',
            'Красный', 'Зеленый', 'Коричневый', 'Бежевый', 'Золотистый',
            'Оранжевый', 'Желтый', 'Фиолетовый', 'Голубой', 'Бордовый'
        ];

        foreach ($colors as $color) {
            CarColor::firstOrCreate(['name' => $color]);
        }

        $count = CarColor::count();
        $this->command->info("✅ Создано цветов авто: {$count}");
    }
}
