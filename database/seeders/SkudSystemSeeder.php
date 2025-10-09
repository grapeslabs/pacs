<?php

namespace Database\Seeders;

use App\Models\GrapeslabsSkudController;
use App\Models\GrapeslabsSkudEvent;
use App\Models\GrapeslabsSkudCommand;
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

        GrapeslabsSkudCommand::factory()
            ->count(20)
            ->create();
            
        $this->command->info('SKUD система заполнена тестовыми данными!');
        $this->command->info('Создано:');
        $this->command->info('- Контроллеров: ' . $controllers->count());
        $this->command->info('- Событий: ' . GrapeslabsSkudEvent::count());
        $this->command->info('- Команд: ' . GrapeslabsSkudCommand::count());
    }
}