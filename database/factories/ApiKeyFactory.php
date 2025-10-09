<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        // 30% шанс что ключ бессрочный
        $isUnlimited = $this->faker->boolean(30);

        return [
            'name' => $this->generateKeyName(),
            // key генерируется автоматически в boot()
            'expires_at' => $isUnlimited ? null : $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'is_unlimited' => $isUnlimited,
            'is_active' => $this->faker->boolean(80), // 80% ключей активны
        ];
    }

    /**
     * Сгенерировать название ключа
     */
    private function generateKeyName(): string
    {
        $prefixes = [
            'Production', 'Development', 'Testing', 'Staging',
            'Mobile App', 'Web App', 'Desktop App', 'API Service',
            'Integration', 'Partner', 'Internal', 'External'
        ];

        $suffixes = [
            'Key', 'Token', 'Access', 'API', 'Service',
            'Client', 'Application', 'System', 'Gateway'
        ];

        return $this->faker->randomElement($prefixes) . ' ' .
            $this->faker->randomElement($suffixes) . ' ' .
            $this->faker->numberBetween(1, 999);
    }

    /**
     * Создать активный ключ
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Создать неактивный ключ
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Создать бессрочный ключ
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unlimited' => true,
            'expires_at' => null,
        ]);
    }

    /**
     * Создать ключ с конкретным названием
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Создать ключ с истекающим сроком
     */
    public function expiresAt(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unlimited' => false,
            'expires_at' => $date,
        ]);
    }
}
