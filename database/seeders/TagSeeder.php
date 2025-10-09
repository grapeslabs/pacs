<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tagList = [
            ['name' => 'Сотрудник', 'short_name' => 'Сотр.'],
            ['name' => 'Посетитель', 'short_name' => 'Посет.'],
            ['name' => 'Водитель', 'short_name' => 'Вод.'],
            ['name' => 'VIP', 'short_name' => 'VIP'],
            ['name' => 'Подрядчик', 'short_name' => 'Подр.'],
            ['name' => 'Постоянный', 'short_name' => 'Пост.'],
        ];

        foreach ($tagList as $tagData) {
            Tag::firstOrCreate(
                ['name' => $tagData['name']],
                ['short_name' => $tagData['short_name']]
            );
        }

        $count = Tag::count();
        $this->command->info("✅ Создано тегов: {$count}");
    }
}
