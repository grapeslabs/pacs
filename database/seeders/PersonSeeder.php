<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Person;

class PersonSeeder extends Seeder
{
    public function run(int $count = 50): void
    {
        $organizations = Organization::all();
        $tags = Tag::all();

        if ($organizations->isEmpty()) {
            $this->command->warn('⚠️ Нет организаций, персоны будут созданы без привязки');
        }

        if ($tags->isEmpty()) {
            $this->command->warn('⚠️ Нет тегов, персоны будут созданы без тегов');
        }

        // Создаем обычных персон
        for ($i = 0; $i < $count; $i++) {
            $organization = $organizations->isNotEmpty() && fake()->boolean(90)
                ? $organizations->random()
                : null;

            $person = Person::factory()
                ->when($organization, fn($factory) => $factory->forOrganization($organization))
                ->create();

            // Привязываем теги
            if ($tags->isNotEmpty()) {
                $personTags = $tags->random(fake()->numberBetween(1, min(3, $tags->count())));
                $person->tags()->attach($personTags->pluck('id'));
            }
        }

        // Создаем специальные тестовые персоны
        $this->createSpecialPersons($organizations, $tags);

        $total = Person::count();
        $this->command->info("✅ Создано персон: {$total}");
    }

    /**
     * Создать специальные тестовые персоны
     */
    private function createSpecialPersons($organizations, $tags): void
    {
        // Иванов Иван - с номером карты 1120034
        $ivanov = Person::factory()
            ->named('Иванов', 'Иван', 'Иванович')
            ->when($organizations->isNotEmpty(), fn($factory) => $factory->forOrganization($organizations->first()))
            ->create([
                'certificate_number' => 'IVAN001',
                'key_uid' => '1120034', // Номер карты для СКУД-контроллера
                'comment' => 'Тестовый пользователь для демо',
            ]);

        if ($tags->isNotEmpty()) {
            $vipTag = $tags->where('name', 'VIP')->first();
            $employeeTag = $tags->where('name', 'Сотрудник')->first();

            $tagIds = [];
            if ($vipTag) $tagIds[] = $vipTag->id;
            if ($employeeTag) $tagIds[] = $employeeTag->id;

            if (!empty($tagIds)) {
                $ivanov->tags()->attach($tagIds);
            }
        }

        // Петров Петр - без организации
        $petrov = Person::factory()
            ->named('Петров', 'Петр', 'Петрович')
            ->withoutOrganization()
            ->create([
                'certificate_number' => 'PETR002',
                'key_uid' => 'person_999', // Для pinterm
                'comment' => 'Внешний посетитель',
            ]);

        if ($tags->isNotEmpty()) {
            $visitorTag = $tags->where('name', 'Посетитель')->first();
            if ($visitorTag) {
                $petrov->tags()->attach($visitorTag->id);
            }
        }
    }
}
