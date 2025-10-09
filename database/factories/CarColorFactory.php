<?php

namespace Database\Factories;

use App\Models\CarColor;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarColorFactory extends Factory
{
    protected $model = CarColor::class;

    public function definition(): array
    {
        $colors = [
            'Белый', 'Черный', 'Серый', 'Серебристый', 'Синий',
            'Красный', 'Зеленый', 'Коричневый', 'Бежевый', 'Золотистый',
            'Оранжевый', 'Желтый', 'Фиолетовый', 'Голубой', 'Бордовый'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($colors),
        ];
    }
}
