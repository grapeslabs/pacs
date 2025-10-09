<?php
// database/factories/TagFactory.php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    // Статическое свойство для отслеживания уже использованных тегов
    protected static $usedTags = [];

    public function definition(): array
    {
        // Предопределенные теги для демо
        $availableTags = [
            ['name' => 'Сотрудник', 'short_name' => 'Сотр.'],
            ['name' => 'Посетитель', 'short_name' => 'Посет.'],
            ['name' => 'Водитель', 'short_name' => 'Вод.'],
            ['name' => 'VIP', 'short_name' => 'VIP'],
            ['name' => 'Подрядчик', 'short_name' => 'Подр.'],
            ['name' => 'Постоянный', 'short_name' => 'Пост.'],
            ['name' => 'Временный', 'short_name' => 'Врем.'],
            ['name' => 'Администрация', 'short_name' => 'Админ.'],
            ['name' => 'Охрана', 'short_name' => 'Охр.'],
            ['name' => 'Техперсонал', 'short_name' => 'Тех.'],
        ];

        // Фильтруем уже использованные теги
        $unusedTags = array_filter($availableTags, function($tag) {
            return !in_array($tag['name'], self::$usedTags);
        });

        // Если все теги использованы, сбрасываем массив
        if (empty($unusedTags)) {
            self::$usedTags = [];
            $unusedTags = $availableTags;
        }

        // Выбираем случайный неиспользованный тег
        $tag = $this->faker->randomElement($unusedTags);

        // Запоминаем, что тег использован
        self::$usedTags[] = $tag['name'];

        return [
            'name' => $tag['name'],
            'short_name' => $tag['short_name'],
        ];
    }

    /**
     * Сбросить использованные теги (для тестирования)
     */
    public static function resetUsedTags(): void
    {
        self::$usedTags = [];
    }
}
