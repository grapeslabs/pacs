<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApiKey;

class ApiKeySeeder extends Seeder
{
    const KEYS_COUNT = 15;

    public function run(): void
    {
        $this->command->info('📊 Создание API ключей...');

        // Основные ключи
        $this->createMainKeys();

        // Дополнительные случайные ключи
        ApiKey::factory()
            ->count(self::KEYS_COUNT)
            ->create();

        // Специальные тестовые ключи
        $this->createSpecialKeys();

        $total = ApiKey::count();
        $this->command->info("✅ Создано API ключей: {$total}");
    }

    /**
     * Создать основные ключи для разных окружений
     */
    private function createMainKeys(): void
    {
        $mainKeys = [
            [
                'name' => 'Production Main API Key',
                'is_unlimited' => false,
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'name' => 'Development API Key',
                'is_unlimited' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Staging API Key',
                'is_unlimited' => false,
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'name' => 'Testing API Key',
                'is_unlimited' => false,
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ],
        ];

        foreach ($mainKeys as $keyData) {
            ApiKey::factory()
                ->state($keyData)
                ->create();
        }
    }

    /**
     * Создать специальные тестовые ключи
     */
    private function createSpecialKeys(): void
    {
        // Просроченный ключ
        ApiKey::factory()
            ->named('Expired API Key')
            ->expiresAt(now()->subMonth())
            ->active()
            ->create();

        // Неактивный ключ
        ApiKey::factory()
            ->named('Deactivated API Key')
            ->unlimited()
            ->inactive()
            ->create();

        // Ключ с истекающим сроком
        ApiKey::factory()
            ->named('Expiring Soon API Key')
            ->expiresAt(now()->addWeek())
            ->active()
            ->create();

        // Ключи для разных сервисов
        $serviceKeys = [
            'Mobile App API Key',
            'Web Dashboard API Key',
            'Partner Integration API Key',
            'Analytics Service API Key',
            'Media Server API Key',
        ];

        foreach ($serviceKeys as $keyName) {
            ApiKey::factory()
                ->named($keyName)
                ->unlimited()
                ->active()
                ->create();
        }
    }
}
