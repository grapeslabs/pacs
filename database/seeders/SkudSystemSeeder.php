<?php

namespace Database\Seeders;

use App\Models\GrapeslabsSkudController;
use App\Models\GrapeslabsSkudEvent;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use Illuminate\Database\Seeder;

class SkudSystemSeeder extends Seeder
{
    public function run(): void
    {
        $controllers = GrapeslabsSkudController::factory()
            ->count(10)
            ->create();

        $controllers->each(function ($controller) {
            GrapeslabsSkudEvent::factory()
                ->count(rand(5, 15))
                ->create([
                    'controller_id' => $controller->id,
                ]);
        });

        $commands = 0;
        foreach ($controllers as $controller) {
            foreach ($this->operations()->random(2) as $payload) {
                $this->createCommand((string) $controller->id, $payload);
                $commands++;
            }
        }

        $this->command->info('SKUD система заполнена тестовыми данными!');
        $this->command->info('Создано:');
        $this->command->info('- Контроллеров: ' . $controllers->count());
        $this->command->info('- Событий: ' . GrapeslabsSkudEvent::count());
        $this->command->info('- Команд: ' . $commands);
    }

    private function operations(): \Illuminate\Support\Collection
    {
        return collect([
            ['operation' => 'set_active', 'active' => 1],
            ['operation' => 'clear_cars'],
            ['operation' => 'add_cars', 'cars' => [['car' => 'А123ВС777']]],
            ['operation' => 'del_cars', 'cars' => [['car' => 'А123ВС777']]],
            ['operation' => 'set_mode', 'modes' => ['entry_mode' => 0, 'exit_mode' => 0]],
        ]);
    }

    private function createCommand(string $controllerId, array $payload): void
    {
        $command = SkudCommand::create([
            'controller_id' => $controllerId,
            'message' => '{}',
        ]);

        $command->update([
            'message' => json_encode(['id' => $command->id] + $payload, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
