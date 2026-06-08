<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\GrapeslabsSkudEvent;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\SelectField;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use MoonShine\Laravel\Handlers\Handler;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\DateRange;
use MoonShine\Support\ListOf;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;

class SkudEventResource extends BaseModelResource
{
    protected string $model = GrapeslabsSkudEvent::class;
    protected string $title = 'Отчеты СКУД';
    protected string $ex_type = 'pingate';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::VIEW);
    }


    /**
     * Модифицируем запрос - показываем только события с картами
     */
    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->whereHas('cardNumber');
    }

    private function getPersonByCardNumber(?string $cardNumber): ?Person
    {
        if (!$cardNumber) return null;

        static $cache = [];

        if (!array_key_exists($cardNumber, $cache)) {
            $cache[$cardNumber] = Person::select('id', 'key_uid', 'first_name', 'last_name', 'middle_name', 'certificate_number', 'organization_id', 'photo')
                ->with('organization:id,short_name,full_name')
                ->where('key_uid', $cardNumber)
                ->first();
        }

        return $cache[$cardNumber];
    }

    private function getControllerType(mixed $controllerId): ?string
    {
        if (!$controllerId || $controllerId === '{}') return null;

        static $cache = [];

        if (!array_key_exists($controllerId, $cache)) {
            $cache[$controllerId] = SkudController::where('id', $controllerId)->value('type');
        }

        return $cache[$controllerId];
    }

    private function getSubjectName(array $eventData): string
    {
        $person = $this->getPersonByCardNumber($eventData['card_number'] ?? null);

        if ($person) return $person->getFullName();
        if (isset($eventData['card_number'])) return 'Карта: ' . $eventData['card_number'];

        return '—';
    }

    private function getCertificateNumber(array $eventData): string
    {
        $person = $this->getPersonByCardNumber($eventData['card_number'] ?? null);

        return $person?->certificate_number ?? '—';
    }

    private function getOrganization(array $eventData): string
    {
        $person = $this->getPersonByCardNumber($eventData['card_number'] ?? null);

        return $person?->organization?->short_name
            ?? $person?->organization?->full_name
            ?? '—';
    }


    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Date::make('Дата/время', 'datetime')
                ->sortable()
                ->format('d.m.Y H:i:s')
                ->withTime(),

            Text::make('Тип оборудования', 'controller_id')
                ->sortable('controller_id')
                ->changeFill(function ($data) {
                    return $this->getControllerType($data->controller_id);
                }),

            Text::make('Серийный номер', 'controller.serial_number')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $query->orderBy(
                        SkudController::select('serial_number')
                            ->whereColumn('id', 'grapeslabs_skud_events.controller_id')
                            ->limit(1),
                        $direction
                    );
                }),

            ColoredSelectField::make('Тип события', 'type', function ($item) {
                $type = (int) $item->type;
                $typeMap = [
                    0 => '0_1',   1 => '0_1',
                    2 => '2_3',   3 => '2_3',
                    4 => '4_5',   5 => '4_5',
                    6 => '6_7',   7 => '6_7',
                    8 => '8_9',   9 => '8_9',
                    10 => '10_11', 11 => '10_11',
                    12 => '12_13', 13 => '12_13',
                    14 => '14_15', 15 => '14_15',
                    16 => '16_17', 17 => '16_17',
                    18 => '18',   19 => '19',
                    20 => '20',   21 => '21',
                    22 => '22_23', 23 => '22_23',
                    26 => '26_27', 27 => '26_27',
                    28 => '28_29', 29 => '28_29',
                    30 => '30_31', 31 => '30_31',
                    32 => '32_33', 33 => '32_33',
                    34 => '34_35', 35 => '34_35',
                    36 => '36',   37 => '37',
                    38 => '38',   39 => '39',
                    40 => '40_41', 41 => '40_41',
                    48 => '48_49', 49 => '48_49',
                    50 => '50_51', 51 => '50_51',
                    52 => '52_53', 53 => '52_53',
                    54 => '54_55', 55 => '54_55',
                    64 => '64',   65 => '65',
                    85 => '85',   86 => '86',
                ];
                return $typeMap[$type] ?? 'unknown';
            })->options([
                '0_1'     => ['label' => 'Открытие кнопкой изнутри',         'color' => 'blue'],
                '2_3'     => ['label' => 'Ключ не найден в банке ключей',     'color' => 'red'],
                '4_5'     => ['label' => 'Ключ найден, дверь открыта',        'color' => 'green'],
                '6_7'     => ['label' => 'Ключ найден, доступ не разрешён',   'color' => 'red'],
                '8_9'     => ['label' => 'Открыто оператором по сети',        'color' => 'blue'],
                '10_11'   => ['label' => 'Дверь заблокирована',               'color' => 'gray'],
                '12_13'   => ['label' => 'Взлом двери',                       'color' => 'pink'],
                '14_15'   => ['label' => 'Дверь оставлена открытой',          'color' => 'orange'],
                '16_17'   => ['label' => 'Проход состоялся',                  'color' => 'green'],
                '18'      => ['label' => 'Срабатывание датчика 1',            'color' => 'orange'],
                '19'      => ['label' => 'Срабатывание датчика 2',            'color' => 'orange'],
                '20'      => ['label' => 'Перезагрузка контроллера',          'color' => 'purple'],
                '21'      => ['label' => 'Событие питания',                   'color' => 'purple'],
                '22_23'   => ['label' => 'Заблокирована кнопка открывания',   'color' => 'gray'],
                '26_27'   => ['label' => 'Нарушение антипассбэка',            'color' => 'red'],
                '28_29'   => ['label' => 'Замок включён (режим Триггер)',     'color' => 'gray'],
                '30_31'   => ['label' => 'Замок выключен (режим Триггер)',    'color' => 'gray'],
                '32_33'   => ['label' => 'Дверь открыта',                     'color' => 'blue'],
                '34_35'   => ['label' => 'Дверь закрыта',                     'color' => 'blue'],
                '36'      => ['label' => 'Управление питанием',               'color' => 'purple'],
                '37'      => ['label' => 'Смена режима работы',               'color' => 'purple'],
                '38'      => ['label' => 'Пожарная тревога',                  'color' => 'pink'],
                '39'      => ['label' => 'Охранная тревога',                  'color' => 'pink'],
                '40_41'   => ['label' => 'Таймаут прохода',                   'color' => 'orange'],
                '48_49'   => ['label' => 'Совершён вход в шлюз',              'color' => 'blue'],
                '50_51'   => ['label' => 'Заблокирован вход в шлюз (занят)',  'color' => 'orange'],
                '52_53'   => ['label' => 'Разрешён вход в шлюз',             'color' => 'green'],
                '54_55'   => ['label' => 'Заблокирован проход (Антипассбек)', 'color' => 'red'],
                '64'      => ['label' => 'Hotel: изменение режима работы',    'color' => 'purple'],
                '65'      => ['label' => 'Hotel: отработка карт',             'color' => 'purple'],
                '85'      => ['label' => 'Идентификация ключа',               'color' => 'blue'],
                '86'      => ['label' => 'Идентификация 7-байтного ключа',    'color' => 'blue'],
                'unknown' => ['label' => 'Неизвестное событие',               'color' => 'gray'],
            ])->sortable('type'),

            Text::make('ФИО/Имя', 'subject_name')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    // Очищаем существующие orderBy
                    $query->getQuery()->orders = null;

                    // Используем cardNumber отношение для сортировки
                    $query->leftJoin('skud_event_persons AS sep', 'sep.event_id', '=', 'grapeslabs_skud_events.id')
                        ->leftJoin('person AS p', 'p.key_uid', '=', 'sep.card_number');

                    // Сортируем через orderByRaw с направлением
                    $query->orderByRaw("CONCAT_WS(' ', p.last_name, p.first_name, p.middle_name) {$direction} NULLS LAST");

                    return $query->addSelect('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $this->getSubjectName($eventData);
                }),

            Image::make('Фото', 'photo', function ($item) {
                $eventData = is_array($item->event) ? $item->event : json_decode($item->event ?? '{}', true);
                $person = $this->getPersonByCardNumber($eventData['card_number'] ?? null);
                return $person?->photo[0] ?? null;
            }),

            Text::make('Номер удостоверения', 'certificate_number')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    $query->getQuery()->orders = null;

                    // Используем cardNumber отношение для сортировки
                    $query->leftJoin('skud_event_persons AS sep', 'sep.event_id', '=', 'grapeslabs_skud_events.id')
                        ->leftJoin('person AS p', 'p.key_uid', '=', 'sep.card_number');

                    return $query->orderBy("p.certificate_number", $direction)
                        ->addSelect('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $this->getCertificateNumber($eventData);
                }),

            Text::make('Организация', 'organization_name')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    $query->getQuery()->orders = null;

                    // Используем cardNumber отношение для сортировки
                    $query->leftJoin('skud_event_persons AS sep', 'sep.event_id', '=', 'grapeslabs_skud_events.id')
                        ->leftJoin('person AS p', 'p.key_uid', '=', 'sep.card_number')
                        ->leftJoin('organizations AS o', 'o.id', '=', 'p.organization_id');

                    return $query->orderBy(DB::raw("COALESCE(o.short_name, o.full_name)"), $direction)
                        ->addSelect('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $this->getOrganization($eventData);
                }),
        ];
    }

    public function filters(): array
    {
        return [
            // Фильтр по диапазону дат
            DateRange::make('Диапазон дат', 'datetime')
                ->format('d.m.Y H:i:s')
                ->withTime()
                ->nullable(),

            Text::make('ФИО/Имя', 'person_fio')
                ->placeholder('Фильтр по ФИО/Имени')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        return $query->whereHas('cardNumber', function($q) use ($value) {
                            $q->whereHas('person', function($personQuery) use ($value) {
                                $personQuery->where('last_name', 'ILIKE', "%{$value}%")
                                    ->orWhere('first_name', 'ILIKE', "%{$value}%")
                                    ->orWhere('middle_name', 'ILIKE', "%{$value}%");
                            });
                        });
                    }
                    return $query;
                }),

            // Фильтр по серийному номеру контроллера
            SelectField::make('Серийный номер', 'controller_serial')
                ->options(function() {
                    // Получаем все контроллеры, исключая pingate
                    return SkudController::where('type', '!=', $this->ex_type)
                        ->orderBy('serial_number')
                        ->get()
                        ->mapWithKeys(function ($controller) {
                            $label = $controller->serial_number;
                            if ($controller->type) {
                                $label .= " ({$controller->type})";
                            }
                            return [$controller->serial_number => $label];
                        })
                        ->toArray();
                })
                ->nullable()
                ->placeholder('Фильтр по серийному номеру')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        // 1. Находим ID контроллера по серийному номеру
                        $controller = SkudController::where('serial_number', $value)
                            ->where('type', '!=', $this->ex_type)
                            ->first();

                        if (!$controller) {
                            return $query->whereRaw('false');
                        }

                        // 2. Фильтруем события по controller_id
                        return $query->where('controller_id', $controller->id);
                    }
                    return $query;
                }),

            // Фильтр по типу события с мультивыбором
            SelectField::make('Типы событий', 'event_types')
                ->options([
                    ['id' => '0_1', 'name' => 'Открытие кнопкой изнутри'],
                    ['id' => '2_3', 'name' => 'Ключ не найден в банке ключей'],
                    ['id' => '4_5', 'name' => 'Ключ найден, дверь открыта'],
                    ['id' => '6_7', 'name' => 'Ключ найден, доступ не разрешен'],
                    ['id' => '8_9', 'name' => 'Открыто оператором по сети'],
                    ['id' => '10_11', 'name' => 'Дверь заблокирована'],
                    ['id' => '12_13', 'name' => 'Взлом двери'],
                    ['id' => '14_15', 'name' => 'Дверь оставлена открытой'],
                    ['id' => '16_17', 'name' => 'Проход состоялся'],
                    ['id' => '18', 'name' => 'Срабатывание датчика 1'],
                    ['id' => '19', 'name' => 'Срабатывание датчика 2'],
                    ['id' => '20', 'name' => 'Перезагрузка контроллера'],
                    ['id' => '21', 'name' => 'Событие питания'],
                    ['id' => '22_23', 'name' => 'Заблокирована кнопка открывания'],
                    ['id' => '26_27', 'name' => 'Нарушение антипассбэка'],
                    ['id' => '28_29', 'name' => 'Замок включен (режим Триггер)'],
                    ['id' => '30_31', 'name' => 'Замок выключен (режим Триггер)'],
                    ['id' => '32_33', 'name' => 'Дверь открыта'],
                    ['id' => '34_35', 'name' => 'Дверь закрыта'],
                    ['id' => '36', 'name' => 'Управление питанием'],
                    ['id' => '37', 'name' => 'Смена режима работы'],
                    ['id' => '38', 'name' => 'Пожарная тревога'],
                    ['id' => '39', 'name' => 'Охранная тревога'],
                    ['id' => '40_41', 'name' => 'Таймаут прохода'],
                    ['id' => '48_49', 'name' => 'Совершен вход в шлюз'],
                    ['id' => '50_51', 'name' => 'Заблокирован вход в шлюз (занят)'],
                    ['id' => '52_53', 'name' => 'Разрешен вход в шлюз'],
                    ['id' => '54_55', 'name' => 'Заблокирован проход (Антипассбек)'],
                    ['id' => '64', 'name' => 'Hotel (Изменение режима работы)'],
                    ['id' => '65', 'name' => 'Hotel (Отработка карт)'],
                    ['id' => '85', 'name' => 'Идентификация ключа'],
                    ['id' => '86', 'name' => 'Идентификация 7-байтного ключа'],
                    ['id' => 'unknown', 'name' => 'Неизвестное событие'],
                ])
                ->multiple(true)
                ->onApply(function (Builder $query, $value) {
                    if (!empty($value)) {
                        $allCodes = [];

                        $selectedTypes = is_array($value) ? $value : [$value];

                        foreach ($selectedTypes as $type) {
                            $codes = $this->getEventCodesByType($type);
                            $allCodes = array_merge($allCodes, $codes);
                        }

                        if (!empty($allCodes)) {
                            return $query->whereIn('type', array_unique($allCodes));
                        }
                    }
                    return $query;
                }),

            // Фильтр по номеру удостоверения
            Text::make('Номер удостоверения', 'certificate_number')
                ->placeholder('Введите номер удостоверения')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        return $query->whereHas('cardNumber', function($q) use ($value) {
                            $q->whereHas('person', function($personQuery) use ($value) {
                                $personQuery->where('certificate_number', 'ILIKE', "%{$value}%");
                            });
                        });
                    }
                    return $query;
                }),
        ];
    }

    public function search(): array
    {
        return ['id',];
    }

    protected function searchQuery(string $terms): void
    {
        $this->newQuery()->where(function (Builder $query) use ($terms) {
            // 1. Поиск по прямым полям события
            $query->where('id', 'ILIKE', "%{$terms}%")

                // 2. Поиск по контроллеру
                ->orWhereHas('controller', function ($q) use ($terms) {
                    $q->where('serial_number', 'ILIKE', "%{$terms}%")
                        ->orWhere('type', 'ILIKE', "%{$terms}%");
                })

                // 3. Поиск по card_number
                ->orWhereHas('cardNumber.person', function ($q) use ($terms) {
                    $q->where('certificate_number', 'ILIKE', "%{$terms}%");
                })

                // 4. Поиск по персоне (через cardNumber -> person)
                ->orWhereHas('cardNumber.person', function ($q) use ($terms) {
                    $q->where(function($subQ) use ($terms) {
                        $subQ->where('last_name', 'ILIKE', "%{$terms}%")
                            ->orWhere('first_name', 'ILIKE', "%{$terms}%")
                            ->orWhere('middle_name', 'ILIKE', "%{$terms}%")
                            ->orWhere('certificate_number', 'ILIKE', "%{$terms}%");
                    });
                })

                // 5. Поиск по организации (через cardNumber -> person -> organization)
                ->orWhereHas('cardNumber.person.organization', function ($q) use ($terms) {
                    $q->where(function($subQ) use ($terms) {
                        $subQ->where('short_name', 'ILIKE', "%{$terms}%")
                            ->orWhere('full_name', 'ILIKE', "%{$terms}%");
                    });
                })

                // 6. Поиск по описаниям типов событий
                ->orWhere(function ($q) use ($terms) {
                    $matchingTypes = $this->findEventTypesByDescription($terms);
                    if (!empty($matchingTypes)) {
                        $q->whereIn('type', $matchingTypes);
                    }
                });
        });
    }

    public function rules($item): array
    {
        return [
            'datetime' => ['required', 'date'],
            'controller_id' => ['required', 'exists:grapeslabs_skud_controllers,id'],
            'type' => ['required', 'string', 'max:50'],
            'event_id' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'array'],
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),

            Text::make('ID события', 'event_id'),

            Date::make('Дата и время', 'datetime')
                ->format('d.m.Y H:i:s')
                ->withTime(),

            Text::make('Тип контроллера', 'controller_type')
                ->changeFill(function(GrapeslabsSkudEvent $item) {
                    return $item->controller->type ?? '—';
                }),

            Text::make('Серийный номер', 'controller_serial')
                ->changeFill(function(GrapeslabsSkudEvent $item) {
                    return $item->controller->serial_number ?? '—';
                }),

            Text::make('ФИО/Имя', 'subject_name')
                ->changeFill(function(GrapeslabsSkudEvent $item) {
                    $eventData = json_decode($item->event ?? '{}', true);
                    return $this->getSubjectName($eventData);
                }),

            Text::make('Номер удостоверения', 'certificate_number')
                ->changeFill(function(GrapeslabsSkudEvent $item) {
                    $eventData = json_decode($item->event ?? '{}', true);
                    return $this->getCertificateNumber($eventData);
                }),

            Text::make('Ключ', 'key_uid')
                ->changeFill(function(GrapeslabsSkudEvent $item) {
                    $eventData = json_decode($item->event ?? '{}', true);

                    if (isset($eventData['card_number'])) {
                        return $eventData['card_number'];
                    }

                    return '—';
                }),

            Text::make('Организация', 'organization_name')
                ->changeFill(function(GrapeslabsSkudEvent $item) {
                    $eventData = json_decode($item->event ?? '{}', true);
                    return $this->getOrganization($eventData);
                }),
        ];
    }

    private function renderImageWithModal(string $src, string $id): string
    {
        return <<<HTML
<div x-data="{ open: false, loaded: false, error: false }" x-init="
    const img = new Image();
    img.onload = () => loaded = true;
    img.onerror = () => error = true;
    img.src = '$src';
">
    <template x-if="!error && loaded">
        <div>
            <img
                src="$src"
                style="max-width: 50px; max-height: 50px; border-radius: 4px; cursor: pointer; object-fit: cover;"
                @click="open = true"
                title="Нажмите для увеличения"
                loading="lazy"
            />

            <div x-show="open" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 x-transition.opacity @click.self="open = false" @keydown.escape.window="open = false" x-cloak>
                <div style="position: relative;">
                    <img src="$src" style="max-width: 90vw; max-height: 90vh; border-radius: 8px;">
                    <button @click="open = false" style="position: absolute; top: -10px; right: -10px; background: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 20px;">×</button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="error">
        <div style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border: 1px dashed #dee2e6; border-radius: 4px;">
            <span style="color: #dc3545; font-size: 12px;">Ошибка загрузки</span>
        </div>
    </template>

    <template x-if="!error && !loaded">
        <div style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 4px;">
            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>
    </template>
</div>
HTML;
    }

    /**
     * Получить коды событий по типу
     */
    private function getEventCodesByType(string $type): array
    {
        $codeMap = [
            '0_1' => [0, 1],
            '2_3' => [2, 3],
            '4_5' => [4, 5],
            '6_7' => [6, 7],
            '8_9' => [8, 9],
            '10_11' => [10, 11],
            '12_13' => [12, 13],
            '14_15' => [14, 15],
            '16_17' => [16, 17],
            '18' => [18],
            '19' => [19],
            '20' => [20],
            '21' => [21],
            '22_23' => [22, 23],
            '26_27' => [26, 27],
            '28_29' => [28, 29],
            '30_31' => [30, 31],
            '32_33' => [32, 33],
            '34_35' => [34, 35],
            '36' => [36],
            '37' => [37],
            '38' => [38],
            '39' => [39],
            '40_41' => [40, 41],
            '48_49' => [48, 49],
            '50_51' => [50, 51],
            '52_53' => [52, 53],
            '54_55' => [54, 55],
            '64' => [64],
            '65' => [65],
            '85' => [85],
            '86' => [86],
            'unknown' => [],
        ];

        return $codeMap[$type] ?? [];
    }

    private function findEventTypesByDescription(string $search): array
    {
        $search = mb_strtolower($search);
        $matchingTypes = [];

        $eventDescriptions = [
            '0_1' => 'Открытие кнопкой изнутри',
            '2_3' => 'Ключ не найден в банке ключей',
            '4_5' => 'Ключ найден, дверь открыта',
            '6_7' => 'Ключ найден, доступ не разрешен',
            '8_9' => 'Открыто оператором по сети',
            '10_11' => 'Дверь заблокирована',
            '12_13' => 'Взлом двери',
            '14_15' => 'Дверь оставлена открытой',
            '16_17' => 'Проход состоялся',
            '18' => 'Срабатывание датчика 1',
            '19' => 'Срабатывание датчика 2',
            '20' => 'Перезагрузка контроллера',
            '21' => 'Событие питания',
            '22_23' => 'Заблокирована кнопка открывания',
            '26_27' => 'Нарушение антипассбэка',
            '28_29' => 'Замок включен (режим Триггер)',
            '30_31' => 'Замок выключен (режим Триггер)',
            '32_33' => 'Дверь открыта',
            '34_35' => 'Дверь закрыта',
            '36' => 'Управление питанием',
            '37' => 'Смена режима работы',
            '38' => 'Пожарная тревога',
            '39' => 'Охранная тревога',
            '40_41' => 'Таймаут прохода',
            '48_49' => 'Совершен вход в шлюз',
            '50_51' => 'Заблокирован вход в шлюз (занят)',
            '52_53' => 'Разрешен вход в шлюз',
            '54_55' => 'Заблокирован проход (Антипассбек)',
            '64' => 'Hotel (Изменение режима работы)',
            '65' => 'Hotel (Отработка карт)',
            '85' => 'Идентификация ключа',
            '86' => 'Идентификация 7-байтного ключа',
            'unknown' => 'Неизвестное событие',
        ];

        foreach ($eventDescriptions as $type => $description) {
            if (mb_stripos($description, $search) !== false) {
                $codes = $this->getEventCodesByType($type);
                $matchingTypes = array_merge($matchingTypes, $codes);
            }
        }

        return array_unique($matchingTypes);
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата/время', 'datetime')
                ->format('d.m.Y H:i:s'),

            Text::make('Тип оборудования', 'controller_id')
                ->changeFill(function ($data) {
                    static $cache = [];
                    $id = $data->controller_id;

                    if (empty($id) || $id === '{}') return null;

                    if (!array_key_exists($id, $cache)) {
                        $cache[$id] = SkudController::where('id', $id)->value('type');
                    }

                    return $cache[$id];
                }),

            Text::make('Серийный номер', 'controller.serial_number'),

            Text::make('Тип события', 'type')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $eventData['event'] ?? '';
                }),

            Text::make('ФИО/Имя', 'subject_name')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $this->getSubjectName($eventData);
                }),

            Text::make('Номер удостоверения', 'certificate_number')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $this->getCertificateNumber($eventData);
                }),

            Text::make('Организация', 'organization_name')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);
                    return $this->getOrganization($eventData);
                }),
        ];
    }
}
