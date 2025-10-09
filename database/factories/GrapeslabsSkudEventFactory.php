<?php

namespace Database\Factories;

use App\Models\GrapeslabsSkudController;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrapeslabsSkudEventFactory extends Factory
{
    public function definition(): array
    {
        $eventTypes = ['access_granted', 'access_denied', 'door_opened', 'door_closed', 'alarm_triggered'];
        
        return [
            'datetime' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'event_id' => $this->faker->uuid(),
            'controller_id' => GrapeslabsSkudController::factory(),
            'type' => $this->faker->randomElement($eventTypes),
            'event' => json_encode([
                'card_number' => $this->faker->randomNumber(6),
                'user_id' => $this->faker->randomNumber(4),
                'door' => $this->faker->randomElement([1, 2, 3, 4]),
                'reason' => $this->faker->optional()->sentence(),
                'temperature' => $this->faker->optional()->randomFloat(1, -10, 40),
            ]),
        ];
    }
}