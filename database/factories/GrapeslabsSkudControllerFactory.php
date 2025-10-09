<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GrapeslabsSkudControllerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'serial_number' => 'SN-' . $this->faker->unique()->randomNumber(6),
            'type' => $this->faker->randomElement(['GRP-100', 'GRP-200', 'GRP-300', 'GRP-400']),
            'ip' => $this->faker->optional(0.8)->ipv4(), // 80% chance to have IP
        ];
    }
}