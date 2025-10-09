<?php

namespace Database\Factories;

use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

class BotFactory extends Factory
{
    protected $model = Bot::class;

    public function definition(): array
    {
        $service = 'telegram'; // Пока только Telegram
        $botNames = [
            'Demo Bot', 'Test Bot', 'Notification Bot', 'Alert Bot',
            'Support Bot', 'Info Bot', 'News Bot', 'Monitor Bot',
            'Event Bot', 'Logger Bot', 'Reporter Bot', 'Watcher Bot'
        ];

        return [
            'name' => $this->faker->randomElement($botNames) . ' ' . $this->faker->randomNumber(3),
            'service' => $service,
            'token' => $this->generateTelegramToken(),
            'api_url' => Bot::DEFAULT_API_URLS['telegram'],
        ];
    }

    /**
     * Генерирует валидный по формату, но фейковый Telegram токен
     */
    private function generateTelegramToken(): string
    {
        // Формат: 9-10 цифр + двоеточие + 35 символов (буквы, цифры, _ -)
        $botId = $this->faker->numberBetween(100000000, 9999999999);
        $hash = $this->faker->regexify('[a-zA-Z0-9_-]{35}');

        return $botId . ':' . $hash;
    }

    /**
     * Создать бота с конкретным именем
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Создать бота с невалидным токеном (для тестирования ошибок)
     */
    public function withInvalidToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => $this->faker->regexify('[A-Za-z0-9]{20}'),
        ]);
    }

    /**
     * Создать бота с кастомным API URL
     */
    public function withApiUrl(string $url): static
    {
        return $this->state(fn (array $attributes) => [
            'api_url' => $url,
        ]);
    }
}
