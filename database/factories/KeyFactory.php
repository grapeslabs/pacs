<?php

namespace Database\Factories;

use App\Models\Key;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeyFactory extends Factory
{
    protected $model = Key::class;

    public function definition(): array
    {
        return [
            'key' => $this->generateMifareKey(),
            'type' => 'Mifare',
            'person_id' => Person::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Генерация случайного Mifare ключа (8 байт в hex)
     */
    private function generateMifareKey(): string
    {
        $bytes = random_bytes(8);
        return strtoupper(bin2hex($bytes));
    }
}