<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GrapeslabsSkudCommandFactory extends Factory
{
    public function definition(): array
    {
        $commands = [
            'open_door' => ['door' => 1, 'duration' => 5],
            'close_door' => ['door' => 1],
            'reboot' => [],
            'update_firmware' => ['version' => '1.2.3'],
            'sync_time' => ['timestamp' => now()->timestamp],
        ];

        $commandType = $this->faker->randomElement(array_keys($commands));

        return [
            'teminal_id' => 'SN-' . $this->faker->randomNumber(6),
            'message' => json_encode([
                'command' => $commandType,
                'parameters' => $commands[$commandType],
                'timestamp' => now()->timestamp,
            ]),
        ];
    }
}
