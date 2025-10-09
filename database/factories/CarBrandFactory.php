<?php

namespace Database\Factories;

use App\Models\CarBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarBrandFactory extends Factory
{
    protected $model = CarBrand::class;

    public function definition(): array
    {
        $brands = [
            'Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen',
            'Hyundai', 'Kia', 'Nissan', 'Lada', 'Skoda', 'Chevrolet', 'Mazda',
            'Volvo', 'Subaru', 'Mitsubishi', 'Peugeot', 'Renault', 'Opel',
            // Добавленные марки (без дубликатов)
            'Lexus', 'Jeep', 'Porsche', 'Infiniti', 'Acura', 'Buick', 'Cadillac',
            'Chrysler', 'Dodge', 'Fiat', 'Jaguar', 'Land Rover', 'Lincoln', 'Mini',
            'Ram', 'Smart', 'Tesla', 'Genesis', 'Alfa Romeo', 'Maserati', 'Bentley',
            'Rolls-Royce', 'Ferrari', 'Lamborghini', 'Aston Martin', 'McLaren',
            'Bugatti', 'GAZ', 'UAZ', 'Seat', 'Citroen', 'Saab', 'Suzuki', 'Isuzu',
            'Daihatsu', 'SsangYong', 'Great Wall', 'Chery', 'Geely', 'BYD', 'Haval',
            'Changan', 'MG', 'Ravon', 'Daewoo', 'ZAZ', 'Moskvich', 'Izh'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($brands),
        ];
    }
}
