<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trigger;
use App\Models\Bot;
use App\Models\Stream;
use App\Models\Tag;
use GrapesLabs\PinvideoSkud\Models\SkudController;

class TriggerSeeder extends Seeder
{
    const TRIGGERS_COUNT = 20;

    public function run(): void
    {
        $bots = Bot::all();
        $tags = Tag::all();

        if ($bots->isEmpty()) {
            $this->command->warn('⚠️ Нет ботов. Триггеры созданы не будут.');
            return;
        }

        $this->command->info('📊 Создание триггеров...');

        // Триггеры для камер
        $this->createCameraTriggers($bots, $tags);

        // Триггеры для терминалов
        $this->createTerminalTriggers($bots, $tags);

        // Триггеры для шлагбаумов
        $this->createBarrierTriggers($bots, $tags);

        // Триггеры для контроллеров
        $this->createControllerTriggers($bots, $tags);

        // Специальные триггеры
        $this->createSpecialTriggers($bots, $tags);

        $total = Trigger::count();
        $this->command->info("✅ Создано триггеров: {$total}");
    }

    /**
     * Создать триггеры для камер
     */
    private function createCameraTriggers($bots, $tags): void
    {
        $streams = Stream::all();

        if ($streams->isEmpty()) {
            return;
        }

        // По 2 триггера на каждую камеру (известное/неизвестное лицо)
        foreach ($streams->take(3) as $stream) {
            // На известное лицо
            Trigger::factory()
                ->forCamera($stream, Trigger::EVENT_KNOWED)
                ->active()
                ->withTags($tags->random(min(2, $tags->count())))
                ->create([
                    'name' => "Камера {$stream->name}: известное лицо",
                    'bot_id' => $bots->random()->id,
                    'data' => [
                        'date' => true,
                        'time' => true,
                        'stream' => true,
                        'lfm' => true,
                        'person_id' => true,
                        'photo_bank' => true,
                        'photo' => false,
                    ],
                ]);

            // На неизвестное лицо
            Trigger::factory()
                ->forCamera($stream, Trigger::EVENT_UNKNOWED)
                ->active()
                ->withTags($tags->random(min(1, $tags->count())))
                ->create([
                    'name' => "Камера {$stream->name}: неизвестное лицо",
                    'bot_id' => $bots->random()->id,
                    'data' => [
                        'date' => true,
                        'time' => true,
                        'stream' => true,
                        'lfm' => false,
                        'person_id' => false,
                        'photo_bank' => false,
                        'photo' => true,
                    ],
                ]);
        }
    }

    /**
     * Создать триггеры для терминалов
     */
    private function createTerminalTriggers($bots, $tags): void
    {
        $terminals = SkudController::where('type', 'pinterm')->get();

        foreach ($terminals->take(4) as $terminal) {
            // Вход
            Trigger::factory()
                ->forTerminal($terminal, Trigger::EVENT_INCOME)
                ->active()
                ->withTags($tags->random(min(2, $tags->count())))
                ->create([
                    'name' => "Терминал {$terminal->serial_number}: вход",
                    'bot_id' => $bots->random()->id,
                ]);

            // Отказ
            Trigger::factory()
                ->forTerminal($terminal, Trigger::EVENT_DENIED)
                ->active()
                ->create([
                    'name' => "Терминал {$terminal->serial_number}: отказ доступа",
                    'bot_id' => $bots->random()->id,
                ]);
        }
    }

    /**
     * Создать триггеры для шлагбаумов
     */
    private function createBarrierTriggers($bots, $tags): void
    {
        $barriers = SkudController::where('type', 'pingate')->get();

        foreach ($barriers as $barrier) {
            // Въезд
            Trigger::factory()
                ->forBarrier($barrier, Trigger::EVENT_INCOME)
                ->active()
                ->create([
                    'name' => "Шлагбаум {$barrier->serial_number}: въезд",
                    'bot_id' => $bots->random()->id,
                ]);

            // Выезд
            Trigger::factory()
                ->forBarrier($barrier, Trigger::EVENT_OUTCOME)
                ->active()
                ->create([
                    'name' => "Шлагбаум {$barrier->serial_number}: выезд",
                    'bot_id' => $bots->random()->id,
                ]);
        }
    }

    /**
     * Создать триггеры для контроллеров
     */
    private function createControllerTriggers($bots, $tags): void
    {
        $controllers = SkudController::whereIn('type', ['z5rweb', 'ironlogic'])->get();

        foreach ($controllers->take(3) as $controller) {
            Trigger::factory()
                ->forController($controller)
                ->active()
                ->create([
                    'name' => "Контроллер {$controller->serial_number}: события",
                    'bot_id' => $bots->random()->id,
                ]);
        }
    }

    /**
     * Создать специальные тестовые триггеры
     */
    private function createSpecialTriggers($bots, $tags): void
    {
        // Главная камера - все события
        $mainStream = Stream::first();
        if ($mainStream) {
            Trigger::factory()
                ->forCamera($mainStream, Trigger::EVENT_KNOWED)
                ->active()
                ->withTags($tags->where('name', 'VIP')->first()?->id)
                ->create([
                    'name' => '🔴 VIP: известные лица',
                    'bot_id' => $bots->where('name', 'like', '%VIP%')->first()?->id ?? $bots->first()->id,
                    'data' => [
                        'date' => true,
                        'time' => true,
                        'stream' => true,
                        'lfm' => true,
                        'person_id' => true,
                        'photo_bank' => true,
                        'photo' => true,
                    ],
                ]);
        }

        // Неактивный триггер для примера (с реальным устройством)
        $terminal = SkudController::where('type', 'pinterm')->first();
        if ($terminal) {
            Trigger::factory()
                ->inactive()
                ->forTerminal($terminal, Trigger::EVENT_INCOME)
                ->create([
                    'name' => '⚪ Неактивный триггер (пример)',
                    'bot_id' => $bots->first()->id,
                ]);
        }
    }
}
