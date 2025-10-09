<?php

namespace Database\Factories;

use App\Models\Trigger;
use App\Models\Bot;
use App\Models\Stream;
use App\Models\Tag;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Database\Eloquent\Factories\Factory;

class TriggerFactory extends Factory
{
    protected $model = Trigger::class;

    public function definition(): array
    {
        $deviceTypes = [
            Trigger::DEVICE_CAMERA,
            Trigger::DEVICE_TERMINAL,
            Trigger::DEVICE_BARIER,
            Trigger::DEVICE_CONTROLLER,
        ];

        $deviceType = $this->faker->randomElement($deviceTypes);

        // Выбираем устройство в зависимости от типа
        $deviceId = $this->getDeviceId($deviceType);

        // Выбираем событие в зависимости от типа устройства
        $eventType = $this->getEventType($deviceType);

        // Настройки данных для уведомления
        $data = [
            'date' => $this->faker->boolean(70),
            'time' => $this->faker->boolean(70),
            'lfm' => $this->faker->boolean(50),
            'person_id' => $this->faker->boolean(30),
            'comment' => $this->faker->boolean(40),
            'photo' => $this->faker->boolean(20),
        ];

        // Для камер добавляем поле stream
        if ($deviceType === Trigger::DEVICE_CAMERA) {
            $data['stream'] = $this->faker->boolean(80);
            $data['photo_bank'] = $this->faker->boolean(60);
        }

        return [
            'is_active' => $this->faker->boolean(80),
            'name' => $this->generateTriggerName($deviceType, $eventType),
            'device_type' => $deviceType,
            'device_id' => $deviceId,
            'bot_id' => Bot::inRandomOrder()->value('id') ?? Bot::factory(),
            'event_type' => $eventType,
            'data' => $data,
        ];
    }

    /**
     * Получить ID устройства по типу
     */
    private function getDeviceId(string $deviceType): ?int
    {
        return match($deviceType) {
            Trigger::DEVICE_CAMERA => Stream::inRandomOrder()->value('id'),
            Trigger::DEVICE_TERMINAL => SkudController::where('type', 'pinterm')->inRandomOrder()->value('id'),
            Trigger::DEVICE_BARIER => SkudController::where('type', 'pingate')->inRandomOrder()->value('id'),
            Trigger::DEVICE_CONTROLLER => SkudController::whereIn('type', ['z5rweb', 'ironlogic'])->inRandomOrder()->value('id'),
            default => null,
        };
    }

    /**
     * Получить тип события в зависимости от устройства
     */
    private function getEventType(string $deviceType): string
    {
        if ($deviceType === Trigger::DEVICE_CAMERA) {
            return $this->faker->randomElement([
                Trigger::EVENT_KNOWED,
                Trigger::EVENT_UNKNOWED,
            ]);
        }

        return $this->faker->randomElement([
            Trigger::EVENT_INCOME,
            Trigger::EVENT_OUTCOME,
            Trigger::EVENT_MANUAL,
            Trigger::EVENT_DENIED,
        ]);
    }

    /**
     * Сгенерировать название триггера
     */
    private function generateTriggerName(string $deviceType, string $eventType): string
    {
        $deviceNames = [
            Trigger::DEVICE_CAMERA => 'Камера',
            Trigger::DEVICE_TERMINAL => 'Терминал',
            Trigger::DEVICE_BARIER => 'Шлагбаум',
            Trigger::DEVICE_CONTROLLER => 'Контроллер',
        ];

        $eventNames = [
            Trigger::EVENT_KNOWED => 'известное лицо',
            Trigger::EVENT_UNKNOWED => 'неизвестное лицо',
            Trigger::EVENT_INCOME => 'вход',
            Trigger::EVENT_OUTCOME => 'выход',
            Trigger::EVENT_MANUAL => 'ручное открытие',
            Trigger::EVENT_DENIED => 'отказ доступа',
        ];

        $deviceName = $deviceNames[$deviceType] ?? 'Устройство';
        $eventName = $eventNames[$eventType] ?? 'событие';

        return $deviceName . ': ' . $eventName . ' #' . $this->faker->numberBetween(1, 999);
    }

    /**
     * Создать активный триггер
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Создать неактивный триггер
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Создать триггер для камеры
     */
    public function forCamera(Stream $stream, string $eventType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => Trigger::DEVICE_CAMERA,
            'device_id' => $stream->id,
            'event_type' => $eventType ?? $this->faker->randomElement([Trigger::EVENT_KNOWED, Trigger::EVENT_UNKNOWED]),
        ]);
    }

    /**
     * Создать триггер для терминала
     */
    public function forTerminal(SkudController $terminal, string $eventType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => Trigger::DEVICE_TERMINAL,
            'device_id' => $terminal->id,
            'event_type' => $eventType ?? $this->faker->randomElement([Trigger::EVENT_INCOME, Trigger::EVENT_OUTCOME, Trigger::EVENT_DENIED]),
        ]);
    }

    /**
     * Создать триггер для шлагбаума
     */
    public function forBarrier(SkudController $barrier, string $eventType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => Trigger::DEVICE_BARIER,
            'device_id' => $barrier->id,
            'event_type' => $eventType ?? $this->faker->randomElement([Trigger::EVENT_INCOME, Trigger::EVENT_OUTCOME, Trigger::EVENT_DENIED]),
        ]);
    }

    /**
     * Создать триггер для контроллера
     */
    public function forController(SkudController $controller, string $eventType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => Trigger::DEVICE_CONTROLLER,
            'device_id' => $controller->id,
            'event_type' => $eventType ?? $this->faker->randomElement([Trigger::EVENT_INCOME, Trigger::EVENT_OUTCOME, Trigger::EVENT_MANUAL]),
        ]);
    }

    /**
     * Привязать теги к триггеру
     */
    public function withTags($tags): static
    {
        return $this->afterCreating(function (Trigger $trigger) use ($tags) {
            $tagIds = $tags instanceof \Illuminate\Support\Collection
                ? $tags->pluck('id')->toArray()
                : (array) $tags;

            $trigger->tags()->attach($tagIds);
        });
    }
}
