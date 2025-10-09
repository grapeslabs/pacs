<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarColor;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * Серии букв для российских номеров
     */
    const LETTERS = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];

    /**
     * Коды регионов
     */
    const REGIONS = [
        '77', '99', '97', '177', '199', // Москва
        '78', '98', '178', // Санкт-Петербург
        '50', '90', '150', '190', // Московская область
        '23', '93', '123', // Краснодарский край
        '16', '116', '716', // Татарстан
        '74', '174', // Челябинская область
        '63', '163', // Самарская область
        '66', '96', '196', // Свердловская область
        '54', '154', // Новосибирская область
        '25', '125', // Приморский край
        '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
    ];

    public function definition(): array
    {
        $types = ['private', 'private', 'private', 'government', 'military', 'trailer'];
        $type = $this->faker->randomElement($types);

        return [
            'license_plate' => $this->generateRussianPlate($type),
            'brand_id' => CarBrand::inRandomOrder()->value('id') ?? CarBrand::factory(),
            'color_id' => CarColor::inRandomOrder()->value('id') ?? CarColor::factory(),
            'organization_id' => $this->faker->boolean(60) ? Organization::inRandomOrder()->value('id') : null,
            'comment' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Генерация российского номерного знака
     */
    private function generateRussianPlate(string $type = 'private'): string
    {
        switch ($type) {
            case 'military':
                return $this->generateMilitaryPlate();
            case 'trailer':
                return $this->generateTrailerPlate();
            default:
                return $this->generateStandardPlate();
        }
    }

    /**
     * Стандартный формат: А123ВС 777
     */
    private function generateStandardPlate(): string
    {
        $letter1 = self::LETTERS[array_rand(self::LETTERS)];
        $numbers = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $letter2 = self::LETTERS[array_rand(self::LETTERS)];
        $letter3 = self::LETTERS[array_rand(self::LETTERS)];
        $region = self::REGIONS[array_rand(self::REGIONS)];

        return $letter1 . $numbers . $letter2 . $letter3 . ' ' . $region;
    }

    /**
     * Военный формат: 1234АВ
     */
    private function generateMilitaryPlate(): string
    {
        $numbers = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $letter1 = self::LETTERS[array_rand(self::LETTERS)];
        $letter2 = self::LETTERS[array_rand(self::LETTERS)];

        return $numbers . $letter1 . $letter2;
    }

    /**
     * Формат прицепа: АВ1234
     */
    private function generateTrailerPlate(): string
    {
        $letter1 = self::LETTERS[array_rand(self::LETTERS)];
        $letter2 = self::LETTERS[array_rand(self::LETTERS)];
        $numbers = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $letter1 . $letter2 . $numbers;
    }

    /**
     * Указать конкретную организацию
     */
    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Создать машину без организации
     */
    public function withoutOrganization(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => null,
        ]);
    }

    /**
     * Создать машину с конкретным номером
     */
    public function withPlate(string $plate): static
    {
        return $this->state(fn (array $attributes) => [
            'license_plate' => $plate,
        ]);
    }

    /**
     * Создать машину с частным номером
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'license_plate' => $this->generateStandardPlate(),
        ]);
    }

    /**
     * Создать машину с военным номером
     */
    public function military(): static
    {
        return $this->state(fn (array $attributes) => [
            'license_plate' => $this->generateMilitaryPlate(),
        ]);
    }

    /**
     * Создать машину с номером прицепа
     */
    public function trailer(): static
    {
        return $this->state(fn (array $attributes) => [
            'license_plate' => $this->generateTrailerPlate(),
        ]);
    }
}
