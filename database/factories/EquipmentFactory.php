<?php

namespace Database\Factories;

use App\Models\Equipment;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    /**
     * Доступные типы оборудования
     */
    const TYPES = [
        'pingate' => 'Шлагбаум',
        'pinterm' => 'Терминал доступа',
        'ironlogic' => 'Контроллер Ironlogic',
        'z5rweb' => 'Контроллер Z5R Web',
    ];

    /**
     * Модели для каждого типа (для названия)
     */
    const MODELS = [
        'pingate' => ['PiGate-100', 'PiGate-200', 'PiGate-300', 'PiGate-Lite'],
        'pinterm' => ['PinTerm-7', 'PinTerm-10', 'PinTerm-Pro', 'PinTerm-Mini'],
        'ironlogic' => ['IL-Controller-1', 'IL-Controller-2', 'IL-Net', 'IL-Pro'],
        'z5rweb' => ['Z5R-WEB-M', 'Z5R-WEB-L', 'Z5R-WEB-XL', 'Z5R-Controller'],
    ];

    /**
     * Локации для описания
     */
    const LOCATIONS = [
        'Главный вход',
        'Запасной выход',
        'Центральная проходная',
        'Парковка',
        'Серверная',
        'Офис 101',
        'Офис 102',
        'Офис 103',
        'Цех №1',
        'Цех №2',
        'Цех №3',
        'Склад А',
        'Склад Б',
        'Склад готовой продукции',
        'Административный корпус',
        'Проходная северная',
        'Проходная южная',
        'КПП №1',
        'КПП №2',
        'Лаборатория',
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(array_keys(self::TYPES));
        $model = $this->faker->randomElement(self::MODELS[$type]);
        $location = $this->faker->randomElement(self::LOCATIONS);

        // Формируем описание с местом установки
        $description = sprintf(
            'Установлен на %s. %s',
            $location,
            $this->faker->randomElement([
                'Обслуживает сотрудников и посетителей.',
                'Работает в штатном режиме.',
                'Подключен к центральному серверу.',
                'Используется для контроля доступа.',
                'Обеспечивает проход в данную зону.',
                'Требует периодической проверки.',
                'Входит в основную систему СКУД.',
                'Доступен 24/7.',
            ])
        );

        return [
            'name' => self::TYPES[$type] . ' ' . $model,
            'description' => $description,
            'type' => $type,
            'skud_controller_sn' => null,
            'person_uid' => $this->faker->optional(0.3)->uuid(),
            'person_name' => null,
        ];
    }

    /**
     * Создать оборудование с конкретной локацией
     */
    public function atLocation(string $location): static
    {
        return $this->state(function (array $attributes) use ($location) {
            $type = $attributes['type'] ?? $this->faker->randomElement(array_keys(self::TYPES));
            $model = $this->faker->randomElement(self::MODELS[$type]);

            return [
                'name' => self::TYPES[$type] . ' ' . $model,
                'description' => "Установлен на {$location}. Обеспечивает контроль доступа в данную зону.",
            ];
        });
    }

    /**
     * Создать оборудование и соответствующий контроллер СКУД
     */
    public function withSkudController(): static
    {
        return $this->afterCreating(function (Equipment $equipment) {
            // Генерируем серийный номер для контроллера
            $serialNumber = strtoupper($equipment->type) . '-' . date('Ymd') . '-' . $this->faker->unique()->numberBetween(1000, 9999);

            // Создаем запись в SkudController
            $skudController = SkudController::create([
                'serial_number' => $serialNumber,
                'type' => $equipment->type,
                'ip' => $this->faker->ipv4(),
            ]);

            // Обновляем equipment с серийным номером контроллера
            $equipment->update([
                'skud_controller_sn' => $skudController->serial_number
            ]);
        });
    }

    /**
     * Создать шлагбаум
     */
    public function pingate(): static
    {
        return $this->state(function (array $attributes) {
            $model = $this->faker->randomElement(self::MODELS['pingate']);
            $location = $this->faker->randomElement(['въезд', 'выезд', 'шлагбаум', 'парковка']);

            return [
                'type' => 'pingate',
                'name' => 'Шлагбаум ' . $model,
                'description' => "Установлен на {$location}. Регулирует въезд/выезд транспорта.",
            ];
        });
    }

    /**
     * Создать терминал доступа
     */
    public function pinterm(): static
    {
        return $this->state(function (array $attributes) {
            $model = $this->faker->randomElement(self::MODELS['pinterm']);
            $location = $this->faker->randomElement(self::LOCATIONS);

            return [
                'type' => 'pinterm',
                'name' => 'Терминал ' . $model,
                'description' => "Установлен на {$location}. Предназначен для идентификации по лицу.",
            ];
        });
    }

    /**
     * Создать контроллер Ironlogic
     */
    public function ironlogic(): static
    {
        return $this->state(function (array $attributes) {
            $model = $this->faker->randomElement(self::MODELS['ironlogic']);
            $location = $this->faker->randomElement(['серверная', 'техпомещение', 'щитовая']);

            return [
                'type' => 'ironlogic',
                'name' => 'Контроллер Ironlogic ' . $model,
                'description' => "Установлен в {$location}. Управляет группой терминалов.",
            ];
        });
    }

    /**
     * Создать контроллер Z5R
     */
    public function z5rweb(): static
    {
        return $this->state(function (array $attributes) {
            $model = $this->faker->randomElement(self::MODELS['z5rweb']);
            $location = $this->faker->randomElement(['серверная', 'техпомещение', 'этажный щит']);

            return [
                'type' => 'z5rweb',
                'name' => 'Контроллер Z5R ' . $model,
                'description' => "Установлен в {$location}. Обеспечивает связь с терминалами.",
            ];
        });
    }

    /**
     * Создать оборудование с привязкой к человеку
     */
    public function withPerson(string $personUid, string $personName): static
    {
        return $this->state(fn (array $attributes) => [
            'person_uid' => $personUid,
            'person_name' => $personName,
            'description' => ($attributes['description'] ?? '') . " Закреплен за {$personName}.",
        ]);
    }

    /**
     * Создать оборудование с конкретным серийным номером контроллера
     */
    public function withControllerSn(string $sn): static
    {
        return $this->state(fn (array $attributes) => [
            'skud_controller_sn' => $sn,
        ]);
    }
}
