<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\GrzReport;
use App\Models\Stream;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrzReportFactory extends Factory
{
    protected $model = GrzReport::class;

    public function definition(): array
    {
        $car = $this->faker->boolean(70) ? Car::inRandomOrder()->first() : null;
        $stream = Stream::inRandomOrder()->first();

        $plateText = $car?->license_plate ?? $this->generateRandomPlate();
        $isAuthorized = $car !== null;

        return [
            'plate_text'   => $plateText,
            'camera_id'    => $stream?->uid,
            'user_id'      => '1',
            'is_authorized' => $isAuthorized,
            'image'        => null,
            'plate'        => null,
            'created_at'   => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function authorized(): static
    {
        return $this->state(function () {
            $car = Car::inRandomOrder()->first();
            return [
                'plate_text'   => $car?->license_plate ?? $this->generateRandomPlate(),
                'is_authorized' => true,
            ];
        });
    }

    public function unauthorized(): static
    {
        return $this->state(fn () => [
            'plate_text'   => $this->generateRandomPlate(),
            'is_authorized' => false,
        ]);
    }

    public function notRecognized(): static
    {
        return $this->state(fn () => [
            'plate_text'   => '',
            'is_authorized' => false,
        ]);
    }

    public function forStream(Stream $stream): static
    {
        return $this->state(fn () => ['camera_id' => $stream->uid]);
    }

    private function generateRandomPlate(): string
    {
        $letters = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];
        $l1 = $letters[array_rand($letters)];
        $l2 = $letters[array_rand($letters)];
        $l3 = $letters[array_rand($letters)];
        $nums = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $region = $this->faker->randomElement(['77', '99', '78', '50', '23', '16', '74', '63', '66', '54']);

        return $l1 . $nums . $l2 . $l3 . ' ' . $region;
    }
}