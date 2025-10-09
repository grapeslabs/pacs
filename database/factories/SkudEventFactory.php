<?php

namespace Database\Factories;

use GrapesLabs\PinvideoSkud\Models\SkudEvent;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use App\Models\Person;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SkudEventFactory extends Factory
{
    protected $model = SkudEvent::class;

    /**
     * Маппинг событий для контроллеров
     */
    const CONTROLLER_EVENTS = [
        'ironlogic' => [
            0 => 'Открытие кнопкой изнутри',
            1 => 'Открытие кнопкой изнутри',
            2 => 'Ключ не найден в банке ключей',
            3 => 'Ключ не найден в банке ключей',
            4 => 'Ключ найден, дверь открыта',
            5 => 'Ключ найден, дверь открыта',
            6 => 'Ключ найден, доступ не разрешен',
            7 => 'Ключ найден, доступ не разрешен',
            8 => 'Открыто оператором по сети',
            9 => 'Открыто оператором по сети',
            10 => 'Дверь заблокирована',
            11 => 'Дверь заблокирована',
            12 => 'Взлом двери',
            13 => 'Взлом двери',
            14 => 'Дверь оставлена открытой',
            15 => 'Дверь оставлена открытой',
            16 => 'Проход состоялся',
            17 => 'Проход состоялся',
            18 => 'Срабатывание датчика 1',
            19 => 'Срабатывание датчика 2',
            20 => 'Перезагрузка контроллера',
            21 => 'Событие питания',
            22 => 'Заблокирована кнопка открывания',
            23 => 'Заблокирована кнопка открывания',
            26 => 'Нарушение антипассбэка',
            27 => 'Нарушение антипассбэка',
            28 => 'Замок включен (режим Триггер)',
            29 => 'Замок включен (режим Триггер)',
            30 => 'Замок выключен (режим Триггер)',
            31 => 'Замок выключен (режим Триггер)',
            32 => 'Дверь открыта',
            33 => 'Дверь открыта',
            34 => 'Дверь закрыта',
            35 => 'Дверь закрыта',
            36 => 'Управление питанием',
            37 => 'Смена режима работы',
            38 => 'Пожарная тревога',
            39 => 'Охранная тревога',
            40 => 'Таймаут прохода',
            41 => 'Таймаут прохода',
            48 => 'Совершен вход в шлюз',
            49 => 'Совершен вход в шлюз',
            50 => 'Заблокирован вход в шлюз (занят)',
            51 => 'Заблокирован вход в шлюз (занят)',
            52 => 'Разрешен вход в шлюз',
            53 => 'Разрешен вход в шлюз',
            54 => 'Заблокирован проход (Антипассбек)',
            55 => 'Заблокирован проход (Антипассбек)',
            64 => 'Hotel (Изменение режима работы)',
            65 => 'Hotel (Отработка карт)',
            85 => 'Идентификация ключа',
            86 => 'Идентификация 7-байтного ключа',
        ],
        'z5rweb' => [
            4 => 'Ключ найден, дверь открыта',
            5 => 'Ключ найден, дверь открыта',
            6 => 'Ключ найден, доступ не разрешен',
            7 => 'Ключ найден, доступ не разрешен',
            16 => 'Проход состоялся',
            17 => 'Проход состоялся',
            32 => 'Дверь открыта',
            33 => 'Дверь открыта',
            34 => 'Дверь закрыта',
            35 => 'Дверь закрыта',
            85 => 'Идентификация ключа',
            86 => 'Идентификация 7-байтного ключа',
        ],
        'pinterm' => [
            4 => 'Ключ найден, дверь открыта',
            5 => 'Ключ найден, дверь открыта',
            6 => 'Ключ найден, доступ не разрешен',
            7 => 'Ключ найден, доступ не разрешен',
            16 => 'Проход состоялся',
            17 => 'Проход состоялся',
            85 => 'Идентификация ключа',
            86 => 'Идентификация 7-байтного ключа',
        ],
        'pingate' => [
            1 => 'Доступ разрешен',
            2 => 'Доступ запрещен',
            4 => 'Въезд автомобиля',
            8 => 'Выезд автомобиля',
            10 => 'Свободный проезд',
            32 => 'Системная ошибка',
        ],
    ];

    public function definition(): array
    {
        $controller = SkudController::inRandomOrder()->first();

        if (!$controller) {
            return [];
        }

        // В 70% случаев генерируем событие с существующими в БД данными
        $useExistingData = $this->faker->boolean(70);

        return $this->generateEvent($controller, $useExistingData);
    }

    /**
     * Генерация события с учетом наличия данных в БД
     */
    private function generateEvent(SkudController $controller, bool $useExistingData): array
    {
        $type = $controller->type;
        $events = self::CONTROLLER_EVENTS[$type] ?? self::CONTROLLER_EVENTS['ironlogic'];

        // Выбираем код события в зависимости от типа и наличия данных
        $eventCode = $this->selectEventCode($type, $useExistingData);
        $eventDescription = $events[$eventCode] ?? 'Неизвестное событие';

        $dateTime = $this->faker->dateTimeBetween('-1 day', 'now');
        $data_s = $this->generateEventData($controller, $eventCode, $eventDescription, $useExistingData);

        return [
            'datetime' => $dateTime,
            'event_id' => $this->faker->unique()->numberBetween(1000, 9999),
            'controller_id' => $controller->id,
            'type' => $eventCode,
            'event' => json_encode($data_s, JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Выбор кода события в зависимости от наличия данных в БД
     */
    private function selectEventCode(string $controllerType, bool $useExistingData): int
    {
        $events = self::CONTROLLER_EVENTS[$controllerType] ?? self::CONTROLLER_EVENTS['ironlogic'];

        if ($controllerType === 'pingate') {
            // Для шлагбаумов: если номер есть в БД - разрешаем въезд/выезд
            if ($useExistingData) {
                return $this->faker->randomElement([1, 4, 8]); // Доступ разрешен, въезд, выезд
            } else {
                return 2; // Доступ запрещен
            }
        }

        // Для контроллеров и терминалов: если персона есть в БД - разрешаем проход
        if ($useExistingData) {
            return $this->faker->randomElement([4, 5, 16, 17]); // Ключ найден, проход состоялся
        } else {
            return $this->faker->randomElement([2, 3, 6, 7]); // Ключ не найден, доступ не разрешен
        }
    }

    /**
     * Генерация data_s в зависимости от типа контроллера
     */
    private function generateEventData($controller, int $eventCode, string $eventDescription, bool $useExistingData): array
    {
        $time = now()->format('Y-m-d H:i:s');
        $sn = $controller->serial_number;

        $data = [
            'time' => $time,
            'sn' => $sn,
            'controller' => $controller->id,
        ];

        switch ($controller->type) {
            case 'pingate':
                // Для шлагбаумов работаем с автомобилями
                if ($useExistingData) {
                    // Берем существующий автомобиль из БД
                    $car = Car::inRandomOrder()->first();
                    $carPlate = $car?->license_plate;
                } else {
                    // Генерируем случайный номер, которого нет в БД
                    $attempts = 0;
                    do {
                        // Русские буквы для номеров (в UTF8)
                        $letters = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];
                        $letter1 = $letters[array_rand($letters)];
                        $letter2 = $letters[array_rand($letters)];
                        $letter3 = $letters[array_rand($letters)];
                        $numbers = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                        $region = rand(1, 199);

                        $carPlate = $letter1 . $numbers . $letter2 . $letter3 . ' ' . $region;

                        // Явно конвертируем в UTF8
                        $carPlate = mb_convert_encoding($carPlate, 'UTF-8', 'UTF-8');

                        // Используем параметризованный запрос через Laravel Query Builder
                        $exists = Car::where('license_plate', '=', $carPlate)->exists();

                        $attempts++;
                        if ($attempts > 100) {
                            // Если не можем найти уникальный номер, берем существующий
                            $car = Car::inRandomOrder()->first();
                            $carPlate = $car?->license_plate ?? 'А123ВС 77';
                            break;
                        }
                    } while ($exists);
                }

                // Убеждаемся что строка в UTF-8
                $carPlate = mb_convert_encoding($carPlate, 'UTF-8', 'UTF-8');

                $data = array_merge($data, [
                    'car_plate' => $carPlate,
                    'event' => $eventDescription,
                    'image_plate' => null,
                    'image_car' => null,
                ]);
                break;

            case 'pinterm':
                // Для терминалов работаем с персонами
                $data = array_merge($data, [
                    'event' => $eventDescription,
                ]);
                break;

            default: // ironlogic, z5rweb
                // Для контроллеров работаем с персонами
                $data = array_merge($data, [
                    'direction' => $this->getDirection($eventCode),
                    'event' => $eventDescription,
                    'flags' => $this->generateFlags($eventCode),
                ]);
                break;
        }

        return $data;
    }

    /**
     * Получить направление по коду события
     */
    private function getDirection(int $code): ?string
    {
        $noDirection = [18, 19, 20, 21, 36, 37, 38, 39, 64, 65, 85, 86];
        if (in_array($code, $noDirection)) {
            return null;
        }

        return $code % 2 === 0 ? 'Вход' : 'Выход';
    }

    /**
     * Генерация флагов для событий IronLogic
     */
    private function generateFlags(int $eventCode): string
    {
        $flag = $this->faker->numberBetween(0, 255);

        switch ($eventCode) {
            case 21:
                $flag = $this->faker->randomElement([0, 1]);
                break;
            case 38:
                $flag = $this->faker->randomElement([0x01, 0x02, 0x04, 0x08, 0x0101, 0x0102]);
                break;
            case 39:
                $flag = $this->faker->randomElement([0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x0101]);
                break;
            case 37:
                $flag = $this->faker->randomElement([0x00, 0x01, 0x02, 0x03, 0x04, 0x08, 0x10, 0x20, 0x81]);
                break;
        }

        return json_encode(['value' => $flag, 'hex' => '0x' . str_pad(dechex($flag), 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * Создать событие для конкретного контроллера
     */
    public function forController(SkudController $controller): static
    {
        return $this->state(function (array $attributes) use ($controller) {
            $useExistingData = $this->faker->boolean(70);
            return $this->generateEvent($controller, $useExistingData);
        });
    }

    /**
     * Создать событие с известной персоной (доступ разрешен)
     */
    public function forPerson(Person $person): static
    {
        return $this->state(function (array $attributes) use ($person) {
            $controller = SkudController::whereIn('type', ['ironlogic', 'z5rweb', 'pinterm'])
                ->inRandomOrder()
                ->first();

            if (!$controller) {
                return [];
            }

            $events = self::CONTROLLER_EVENTS[$controller->type] ?? self::CONTROLLER_EVENTS['ironlogic'];

            // Выбираем коды событий в зависимости от типа контроллера
            if ($controller->type === 'pinterm') {
                // Для pinterm используем person_{id}
                $cardNumber = 'person_' . $person->id;
                $availableCodes = [4, 5, 16, 17];
            } else {
                // Для ironlogic и z5rweb используем номер карты
                // Если у персоны нет номера карты, генерируем
                $cardNumber = $person->key_uid ?? $this->faker->numberBetween(1000000, 9999999);
                $availableCodes = [4, 5, 16, 17];
            }

            $eventCode = $this->faker->randomElement($availableCodes);
            $eventDescription = $events[$eventCode] ?? 'Доступ разрешен';

            $data_s = $this->generateEventData($controller, $eventCode, $eventDescription, true);
            $data_s['card_number'] = $cardNumber;

            return [
                'datetime' => $this->faker->dateTimeBetween('-1 day', 'now'),
                'event_id' => $this->faker->unique()->numberBetween(1000, 9999),
                'controller_id' => $controller->id,
                'type' => $eventCode,
                'event' => json_encode($data_s, JSON_UNESCAPED_UNICODE),
            ];
        });
    }

    /**
     * Создать событие с неизвестной персоной (доступ запрещен)
     */
    public function forUnknownPerson(): static
    {
        return $this->state(function (array $attributes) {
            $controller = SkudController::whereIn('type', ['ironlogic', 'z5rweb', 'pinterm'])
                ->inRandomOrder()
                ->first();

            if (!$controller) {
                return [];
            }

            $events = self::CONTROLLER_EVENTS[$controller->type] ?? self::CONTROLLER_EVENTS['ironlogic'];

            // Генерируем неизвестный номер карты
            if ($controller->type === 'pinterm') {
                // Для pinterm формат person_{id} с несуществующим ID
                $unknownId = $this->faker->numberBetween(10000, 99999);
                $cardNumber = 'person_' . $unknownId;
                $availableCodes = [6, 7];
            } else {
                // Для ironlogic и z5rweb неизвестный номер карты
                do {
                    $cardNumber = $this->faker->numberBetween(1000000, 9999999);
                } while (Person::where('key_uid', $cardNumber)->exists());
                $availableCodes = [2, 3, 6, 7];
            }

            $eventCode = $this->faker->randomElement($availableCodes);
            $eventDescription = $events[$eventCode] ?? 'Доступ запрещен';

            $data_s = $this->generateEventData($controller, $eventCode, $eventDescription, false);
            $data_s['card_number'] = $cardNumber;

            return [
                'datetime' => $this->faker->dateTimeBetween('-1 day', 'now'),
                'event_id' => $this->faker->unique()->numberBetween(1000, 9999),
                'controller_id' => $controller->id,
                'type' => $eventCode,
                'event' => json_encode($data_s, JSON_UNESCAPED_UNICODE),
            ];
        });
    }

    /**
     * Создать событие для конкретного автомобиля (доступ разрешен)
     */
    public function forCar(Car $car): static
    {
        return $this->state(function (array $attributes) use ($car) {
            $controller = SkudController::where('type', 'pingate')
                ->inRandomOrder()
                ->first();

            if (!$controller) {
                return [];
            }

            // События успешного проезда
            $eventCode = $this->faker->randomElement([1, 4, 8]);
            $eventDescription = self::CONTROLLER_EVENTS['pingate'][$eventCode];

            $data_s = [
                'time' => now()->format('Y-m-d H:i:s'),
                'sn' => $controller->serial_number,
                'controller' => $controller->id,
                'car_plate' => $car->license_plate,
                'event' => $eventDescription,
                'image_plate' => null,
                'image_car' => null,
            ];

            return [
                'datetime' => $this->faker->dateTimeBetween('-1 day', 'now'),
                'event_id' => $this->faker->unique()->numberBetween(1000, 9999),
                'controller_id' => $controller->id,
                'type' => $eventCode,
                'event' => json_encode($data_s, JSON_UNESCAPED_UNICODE),
            ];
        });
    }

    /**
     * Создать событие с неизвестным автомобилем (доступ запрещен)
     */
    public function forUnknownCar(): static
    {
        return $this->state(function (array $attributes) {
            $controller = SkudController::where('type', 'pingate')
                ->inRandomOrder()
                ->first();

            if (!$controller) {
                return [];
            }

            $eventCode = 2; // Доступ запрещен
            $eventDescription = self::CONTROLLER_EVENTS['pingate'][$eventCode];

            // Генерируем номер, которого нет в БД
            $attempts = 0;
            do {
                $letters = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];
                $letter1 = $letters[array_rand($letters)];
                $letter2 = $letters[array_rand($letters)];
                $letter3 = $letters[array_rand($letters)];
                $numbers = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                $region = rand(1, 199);

                $carPlate = $letter1 . $numbers . $letter2 . $letter3 . ' ' . $region;

                // Явно конвертируем в UTF8
                $carPlate = mb_convert_encoding($carPlate, 'UTF-8', 'UTF-8');

                // Используем параметризованный запрос
                $exists = Car::where('license_plate', '=', $carPlate)->exists();

                $attempts++;
                if ($attempts > 100) {
                    $carPlate = 'А123ВС 77'; // Запасной вариант
                    $carPlate = mb_convert_encoding($carPlate, 'UTF-8', 'UTF-8');
                    break;
                }
            } while ($exists);

            $data_s = [
                'time' => now()->format('Y-m-d H:i:s'),
                'sn' => $controller->serial_number,
                'controller' => $controller->id,
                'car_plate' => $carPlate,
                'event' => $eventDescription,
                'image_plate' => null,
                'image_car' => null,
            ];

            return [
                'datetime' => $this->faker->dateTimeBetween('-1 day', 'now'),
                'event_id' => $this->faker->unique()->numberBetween(1000, 9999),
                'controller_id' => $controller->id,
                'type' => $eventCode,
                'event' => json_encode($data_s, JSON_UNESCAPED_UNICODE),
            ];
        });
    }

    /**
     * Вспомогательный метод для безопасного создания номера
     */
    private function generateSafeLicensePlate(): string
    {
        $letters = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];
        $letter1 = $letters[array_rand($letters)];
        $letter2 = $letters[array_rand($letters)];
        $letter3 = $letters[array_rand($letters)];
        $numbers = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $region = rand(1, 199);

        $plate = $letter1 . $numbers . $letter2 . $letter3 . ' ' . $region;

        // Конвертируем в UTF-8 и удаляем некорректные символы
        $plate = mb_convert_encoding($plate, 'UTF-8', 'UTF-8');
        $plate = preg_replace('/[^\x{0410}-\x{042F}\x{0430}-\x{044F}\d\s]/u', '', $plate);

        return $plate;
    }


    /**
     * Создать событие с конкретным кодом
     */
    public function withEventCode(int $code): static
    {
        return $this->state(function (array $attributes) use ($code) {
            $controller = SkudController::find($attributes['controller_id'] ?? null);
            if (!$controller) {
                return [];
            }

            $events = self::CONTROLLER_EVENTS[$controller->type] ?? self::CONTROLLER_EVENTS['ironlogic'];
            $eventDescription = $events[$code] ?? 'Неизвестное событие';

            // Определяем, есть ли данные в БД по коду события
            $useExistingData = in_array($code, [1,4,5,8,16,17]) ? true : false;

            $data_s = $this->generateEventData($controller, $code, $eventDescription, $useExistingData);

            return [
                'type' => $code,
                'event' => json_encode($data_s, JSON_UNESCAPED_UNICODE),
            ];
        });
    }
}
