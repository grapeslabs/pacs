<?php

namespace Database\Seeders;

use App\Models\Key;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KeySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('keys')->truncate();

        $people = Person::all();
        
        if ($people->isEmpty()) {
            $this->command->info('Создаем тестовых персон...');
            
            $people = [];
            for ($i = 1; $i <= 10; $i++) {
                $people[] = Person::create([
                    'name' => 'Тестовая персона ' . $i,
                    'email' => 'person' . $i . '@example.com',
                    'created_at' => now()->subDays(rand(1, 365)),
                    'updated_at' => now()->subDays(rand(1, 365)),
                ]);
            }
            
            $this->command->info('Создано ' . count($people) . ' тестовых персон');
        }

        $this->command->info('Создаем тестовые ключи...');

        // Создаем по 1-3 ключа для каждой персоны
        $totalKeys = 0;
        foreach ($people as $person) {
            $keyCount = rand(1, 3);
            
            for ($i = 0; $i < $keyCount; $i++) {
                Key::create([
                    'key' => $this->generateMifareKey(),
                    'type' => 'Mifare',
                    'person_id' => $person->id,
                    'created_at' => now()->subDays(rand(1, 365)),
                    'updated_at' => now()->subDays(rand(1, 365)),
                ]);
                $totalKeys++;
            }
        }

        $this->createSpecificTestKeys();
        $totalKeys += 3;

        $this->command->info("Успешно создано {$totalKeys} тестовых ключей");
    }

    /**
     * Генерация случайного Mifare ключа (8 байт в hex)
     */
    private function generateMifareKey(): string
    {
        $bytes = random_bytes(8);
        return strtoupper(bin2hex($bytes));
    }

    private function createSpecificTestKeys(): void
    {
        $testKeys = [
            [
                'key' => 'A1B2C3D4E5F67890',
                'type' => 'Mifare',
                'person_id' => Person::inRandomOrder()->first()->id,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'key' => '1234567890ABCDEF',
                'type' => 'Mifare', 
                'person_id' => Person::inRandomOrder()->first()->id,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'key' => 'FEDCBA0987654321',
                'type' => 'Mifare',
                'person_id' => Person::inRandomOrder()->first()->id,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
        ];

        foreach ($testKeys as $testKey) {
            Key::create($testKey);
        }
    }
}