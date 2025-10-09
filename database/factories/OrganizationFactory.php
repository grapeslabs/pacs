<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $types = ['ООО', 'АО', 'ПАО', 'ЗАО', 'ИП'];
        $businessTypes = ['Торговая', 'Строительная', 'Производственная', 'Транспортная', 'Консалтинговая', 'IT'];
        $names = ['Альфа', 'Бета', 'Гамма', 'Дельта', 'Омега', 'Сигма', 'Вега', 'Норд', 'Юг', 'Восток', 'Запад'];

        $type = $this->faker->randomElement($types);
        $businessType = $this->faker->randomElement($businessTypes);
        $name = $this->faker->randomElement($names);

        return [
            'inn' => $this->faker->numerify('##########'), // 10 цифр
            'full_name' => $type . ' "' . $businessType . ' компания ' . $name . '"',
            'short_name' => $type . ' "' . $name . '"',
            'address' => $this->faker->address(),
            'contact_data' => $this->generateContactData(),
            'comment' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /*
     * Генерация контактных данных
     */
    private function generateContactData(): string
    {
        $lastName = $this->faker->lastName('male');
        $firstName = $this->faker->firstName('male');
        $middleName = $this->faker->boolean(90) ? $this->faker->middleName('male') : '';
        $phone = $this->faker->phoneNumber();

        $director = trim("{$lastName} {$firstName} {$middleName}");

        return "Руководитель: {$director} {$phone}";
    }

    /**
     * Создать организацию с конкретным ИНН
     */
    public function withInn(string $inn): static
    {
        return $this->state(fn (array $attributes) => [
            'inn' => $inn,
        ]);
    }

    /**
     * Создать организацию без комментария
     */
    public function withoutComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => null,
        ]);
    }
}
