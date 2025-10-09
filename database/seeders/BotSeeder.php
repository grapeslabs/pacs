<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bot;

class BotSeeder extends Seeder
{
    public function run(int $count = 5): void
    {
        // Создаем основных ботов
        $bots = Bot::factory()
            ->count($count)
            ->create();

        // Создаем одного бота с невалидным токеном
        $invalidBot = Bot::factory()
            ->named('Broken Bot')
            ->withInvalidToken()
            ->create();

        // Создаем специальных ботов
        $this->createSpecialBots();

        $total = Bot::count();
        $this->command->info("✅ Создано ботов: {$total}");
    }

    /**
     * Создать специальных ботов с конкретными именами
     */
    private function createSpecialBots(): void
    {
        $specialBots = [
            [
                'name' => 'Main Notification Bot',
                'service' => 'telegram',
                'token' => '1234567890:ABCdefGHIjklMNOpqrsTUVwxyzABCDefghij',
            ],
            [
                'name' => 'Alert System Bot',
                'service' => 'telegram',
                'token' => '9876543210:ZYXwvuTSRqponMLKJihgfedCBAzyxwvutsrqp',
            ],
            [
                'name' => 'Demo Support Bot',
                'service' => 'telegram',
                'token' => '5555555555:TokenForDemoPurposesOnly1234567890ABC',
            ],
        ];

        foreach ($specialBots as $botData) {
            Bot::firstOrCreate(
                ['name' => $botData['name']],
                [
                    'service' => $botData['service'],
                    'token' => $botData['token'],
                    'api_url' => Bot::DEFAULT_API_URLS['telegram'],
                ]
            );
        }
    }
}
