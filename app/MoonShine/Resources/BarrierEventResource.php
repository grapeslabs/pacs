<?php

namespace App\MoonShine\Resources;

//use GrapesLabs\PinvideoSkud\Models\SkudEvent;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use App\Models\GrapeslabsSkudEvent;
use Illuminate\Support\Facades\DB;
use App\Models\Car;
use App\Models\Person;
use App\Models\Organization;
use App\Models\CarBrand;
use App\Models\CarColor;
use App\Models\SkudEventCarPlate;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Preview;
use MoonShine\Support\ListOf;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;

class BarrierEventResource extends BaseModelResource
{
    protected string $model = GrapeslabsSkudEvent::class;
    protected string $title = 'События автомобили';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->only(Action::VIEW)
            ;
    }

    /**
     * Добавляем join к таблице car_plates
     */
    private function withCarPlate(Builder $query): Builder
    {
        return $query->join('skud_event_car_plates', function ($join) {
            $join->on('grapeslabs_skud_events.id', '=', 'skud_event_car_plates.event_id');
        })->select('grapeslabs_skud_events.*');
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->whereHas('carPlate');
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make('Серийный номер оборудования', 'controller.serial_number')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $query->orderBy(
                        SkudController::select('serial_number')
                            ->whereColumn('id', 'grapeslabs_skud_events.controller_id')
                            ->limit(1),
                        $direction
                    );
                }),

            Date::make('Дата/время', 'datetime')
                ->sortable()
                ->format('d.m.Y H:i:s')
                ->withTime(),

            Text::make('Тип события', 'type')
                ->sortable('type')
                ->changeFill(function ($data) {
                    $info = [];
                    $eventData = json_decode($data->event ?? [], true);

                    if (isset($eventData['event'])) {
                        $info[] = $eventData['event'];
                    }

                    return implode(', ', $info);
                }),

            Text::make('ГРЗ', 'car_plate')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $this->withCarPlate($query)
                        ->orderBy('skud_event_car_plates.car_plate', $direction)
                        ->select('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    // Получаем номер из новой таблицы
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');
                    return $carPlate ?? '—';
                }),

            Text::make('Марка', 'car_brand')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $this->withCarPlate($query)
                        ->orderByRaw("
                            (
                                SELECT car_brands.name
                                FROM cars
                                JOIN car_brands ON cars.brand_id = car_brands.id
                                WHERE cars.license_plate = skud_event_car_plates.car_plate
                                LIMIT 1
                            ) {$direction} NULLS LAST
                        ")
                        ->select('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    // Получаем номер из новой таблицы
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) {
                        return '—';
                    }

                    $car = Car::where('license_plate', $carPlate)
                        ->with('brand')
                        ->first();

                    return $car && $car->brand ? $car->brand->name : '—';
                }),

            Text::make('Цвет', 'car_color')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $this->withCarPlate($query)
                        ->orderByRaw("
                            (
                                SELECT car_colors.name
                                FROM cars
                                JOIN car_colors ON cars.color_id = car_colors.id
                                WHERE cars.license_plate = skud_event_car_plates.car_plate
                                LIMIT 1
                            ) {$direction} NULLS LAST
                        ")
                        ->select('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('color')
                        ->first();

                    return $car->color->name ?? '—';
                }),

            Text::make('ФИО персоны', 'person_name')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $this->withCarPlate($query)
                        ->orderByRaw("
                            (
                                SELECT CONCAT_WS(' ', person.last_name, person.first_name, person.middle_name)
                                FROM cars
                                JOIN car_person ON cars.id = car_person.car_id
                                JOIN person ON car_person.person_id = person.id
                                WHERE cars.license_plate = skud_event_car_plates.car_plate
                                ORDER BY person.last_name
                                LIMIT 1
                            ) {$direction} NULLS LAST
                        ")
                        ->select('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('people')
                        ->first();

                    if ($car && $car->people->isNotEmpty()) {
                        return $car->people->map(function($person) {
                            return trim($person->last_name . ' ' . $person->first_name . ' ' . ($person->middle_name ?? ''));
                        })->implode(', ');
                    }

                    return '—';
                }),

            Text::make('Организация', 'organization_name')
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $this->withCarPlate($query)
                        ->orderByRaw("
                            (
                                SELECT COALESCE(org.short_name, org.full_name)
                                FROM cars
                                LEFT JOIN organizations org ON cars.organization_id = org.id
                                LEFT JOIN car_person cp ON cars.id = cp.car_id
                                LEFT JOIN person p ON cp.person_id = p.id
                                LEFT JOIN organizations porg ON p.organization_id = porg.id
                                WHERE cars.license_plate = skud_event_car_plates.car_plate
                                ORDER BY COALESCE(org.short_name, org.full_name, porg.short_name, porg.full_name)
                                LIMIT 1
                            ) {$direction} NULLS LAST
                        ")
                        ->select('grapeslabs_skud_events.*');
                })
                ->changeFill(function ($data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('organization')
                        ->first();

                    if ($car && $car->organization) {
                        return $car->organization->short_name ?? $car->organization->full_name;
                    }

                    // Попробуем найти через персону
                    if ($car && $car->people->isNotEmpty()) {
                        $person = $car->people->first();
                        if ($person->organization) {
                            return $person->organization->short_name ?? $person->organization->full_name;
                        }
                    }

                    return '—';
                }),

            Preview::make('Фото номера', 'image_plate')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);

                    if (!empty($eventData['image_plate'])) {
                        $base64 = $eventData['image_plate'];
                        $src = str_contains($base64, 'base64,')
                            ? $base64
                            : 'data:image/jpeg;base64,' . $base64;

                        return $this->renderImageWithModal($src, $data->id ?? uniqid());
                    }

                    return '—';
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

            // Фильтр по типу события
            Select::make('Тип события', 'event_type')
                ->options([
                    1 => 'Доступ разрешен',
                    2 => 'Доступ запрещен',
                    4 => 'Въезд автомобиля',
                    8 => 'Выезд автомобиля',
                    10 => 'Свободный проезд',
                    32 => 'Системная ошибка',
                ])
                ->nullable()
                ->searchable()
                ->placeholder('Фильтр по типу событий')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        return $query->where('type', $value);
                    }
                    return $query;
                }),

            // Фильтр по госномеру
            Text::make('ГРЗ', 'car_plate')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        $normalizedValue = mb_strtoupper(preg_replace('/\s+/', '', $value));

                        return $query->whereExists(function ($q) use ($normalizedValue) {
                            $q->select(DB::raw(1))
                                ->from('skud_event_car_plates')
                                ->whereRaw('skud_event_car_plates.event_id = grapeslabs_skud_events.id')
                                ->where('skud_event_car_plates.car_plate', 'ILIKE', "%{$normalizedValue}%");
                        });
                    }
                    return $query;
                })
                ->placeholder('Фильтр по ГРЗ'),

            // Фильтр по марке автомобиля
            Select::make('Марка автомобиля', 'car_brand')
                ->options(
                    CarBrand::query()
                        ->orderBy('name')
                        ->get()
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->nullable()
                ->searchable()
                ->placeholder('Фильтр по марке автомобиля')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        $carPlates = Car::where('brand_id', $value)
                            ->pluck('license_plate')
                            ->toArray();

                        if (empty($carPlates)) {
                            return $query->where('id', 0);
                        }

                        return $query->whereExists(function ($q) use ($carPlates) {
                            $q->select(DB::raw(1))
                                ->from('skud_event_car_plates')
                                ->whereRaw('skud_event_car_plates.event_id = grapeslabs_skud_events.id')
                                ->whereIn('skud_event_car_plates.car_plate', $carPlates);
                        });
                    }
                    return $query;
                }),

            // Фильтр по цвету автомобиля
            Select::make('Цвет автомобиля', 'car_color')
                ->options(
                    CarColor::query()
                        ->orderBy('name')
                        ->get()
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->nullable()
                ->searchable()
                ->placeholder('Фильтр по цвету автомобиля')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        $carPlates = Car::where('color_id', $value)
                            ->pluck('license_plate')
                            ->toArray();

                        if (empty($carPlates)) {
                            return $query->where('id', 0);
                        }

                        return $query->whereExists(function ($q) use ($carPlates) {
                            $q->select(DB::raw(1))
                                ->from('skud_event_car_plates')
                                ->whereRaw('skud_event_car_plates.event_id = grapeslabs_skud_events.id')
                                ->whereIn('skud_event_car_plates.car_plate', $carPlates);
                        });
                    }
                    return $query;
                }),

            // Фильтр по ФИО персоны
            Text::make('ФИО персоны', 'person_name')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        $people = Person::where('last_name', 'ILIKE', "%{$value}%")
                            ->orWhere('first_name', 'ILIKE', "%{$value}%")
                            ->orWhere('middle_name', 'ILIKE', "%{$value}%")
                            ->get();

                        if ($people->isNotEmpty()) {
                            $carPlates = [];
                            foreach ($people as $person) {
                                $personCarPlates = $person->cars()->pluck('license_plate')->toArray();
                                $carPlates = array_merge($carPlates, $personCarPlates);
                            }

                            $carPlates = array_unique($carPlates);

                            if (!empty($carPlates)) {
                                return $query->whereExists(function ($q) use ($carPlates) {
                                    $q->select(DB::raw(1))
                                        ->from('skud_event_car_plates')
                                        ->whereRaw('skud_event_car_plates.event_id = grapeslabs_skud_events.id')
                                        ->whereIn('skud_event_car_plates.car_plate', $carPlates);
                                });
                            }
                        }

                        return $query->where('id', 0);
                    }
                    return $query;
                })
                ->placeholder('Фильтр по ФИО персоны'),

            // Фильтр по организации
            Select::make('Организация', 'organization')
                ->options(
                    Organization::query()
                        ->orderBy('short_name')
                        ->get()
                        ->mapWithKeys(function ($org) {
                            $name = $org->short_name ?? $org->full_name;
                            return [$org->id => $name];
                        })
                        ->toArray()
                )
                ->nullable()
                ->searchable()
                ->placeholder('Фильтр по организации')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        // 1. Автомобили, принадлежащие организации
                        $orgCars = Car::where('organization_id', $value)
                            ->pluck('license_plate')
                            ->toArray();

                        // 2. Автомобили сотрудников организации
                        $peopleIds = Person::where('organization_id', $value)->pluck('id');
                        $peopleCars = DB::table('car_person')
                            ->join('cars', 'car_person.car_id', '=', 'cars.id')
                            ->whereIn('car_person.person_id', $peopleIds)
                            ->pluck('cars.license_plate')
                            ->toArray();

                        $allPlates = array_unique(array_merge($orgCars, $peopleCars));

                        if (!empty($allPlates)) {
                            return $query->whereExists(function ($q) use ($allPlates) {
                                $q->select(DB::raw(1))
                                    ->from('skud_event_car_plates')
                                    ->whereRaw('skud_event_car_plates.event_id = grapeslabs_skud_events.id')
                                    ->whereIn('skud_event_car_plates.car_plate', $allPlates);
                            });
                        }

                        return $query->where('id', 0);
                    }
                    return $query;
                }),

            // Фильтр по серийному номеру оборудования
            Select::make('Оборудование', 'controller_id')
                ->options(
                    SkudController::query()
                        ->select('serial_number', 'id', 'type')
                        ->get()
                        ->mapWithKeys(function ($controller) {
                            return [$controller->id => "{$controller->serial_number} ({$controller->type})"];
                        })
                        ->toArray()
                )
                ->nullable()
                ->searchable()
                ->placeholder('Фильтр по типу оборудования')
                ->onApply(function (Builder $query, $value) {
                    if ($value) {
                        return $query->where('controller_id', $value);
                    }
                    return $query;
                }),
        ];
    }

    public function search(): array
    {
        return [];
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
            Date::make('Дата и время', 'datetime')
                ->format('d.m.Y H:i:s')
                ->withTime(),
            Text::make('Контроллер', 'controller_info')
                ->changeFill(function(SkudEvent $item) {
                    return $item->controller->serial_number . ' (' . $item->controller->type . ')';
                }),
            Text::make('Тип события', 'type')
                ->changeFill(function(SkudEvent $item) {
                    $eventData = json_decode($item->event ?? [], true);
                    $event_type = $eventData['event'] ?? null;

                    return $event_type ?? '—';
                }),
            Text::make('ID события', 'event_id'),
            Text::make('ГРЗ', 'car_plate_detail')
                ->changeFill(function(SkudEvent $item) {
                    $carPlate = SkudEventCarPlate::where('event_id', $item->id)->value('car_plate');
                    return $carPlate ?? '—';
                }),

            Text::make('Марка', 'car_brand')
                ->changeFill(function (SkudEvent $data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('brand')
                        ->first();

                    return $car->brand->name ?? '—';
                }),

            Text::make('Цвет', 'car_color')
                ->changeFill(function (SkudEvent $data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('color')
                        ->first();

                    return $car->color->name ?? '—';
                }),

            Text::make('ФИО персоны', 'person_name')
                ->changeFill(function (SkudEvent $data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('people')
                        ->first();

                    if ($car && $car->people->isNotEmpty()) {
                        return $car->people->map(function($person) {
                            return trim($person->last_name . ' ' . $person->first_name . ' ' . ($person->middle_name ?? ''));
                        })->implode(', ');
                    }

                    return '—';
                }),

            Text::make('Организация', 'organization_name')
                ->changeFill(function (SkudEvent $data) {
                    $carPlate = SkudEventCarPlate::where('event_id', $data->id)->value('car_plate');

                    if (!$carPlate) return '—';

                    $car = Car::where('license_plate', $carPlate)
                        ->with('organization')
                        ->first();

                    if ($car && $car->organization) {
                        return $car->organization->short_name ?? $car->organization->full_name;
                    }

                    // Попробуем найти через персону
                    if ($car && $car->people->isNotEmpty()) {
                        $person = $car->people->first();
                        if ($person->organization) {
                            return $person->organization->short_name ?? $person->organization->full_name;
                        }
                    }

                    return '—';
                }),

            Preview::make('Фото машины', 'image_car')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);

                    if (!empty($eventData['image_car'])) {
                        $base64 = $eventData['image_car'];
                        $src = str_contains($base64, 'base64,')
                            ? $base64
                            : 'data:image/jpeg;base64,' . $base64;

                        return $this->renderImageWithModal($src, $data->id ?? uniqid());
                    }

                    return '—';
                }),
            Preview::make('Фото номера', 'image_plate')
                ->changeFill(function ($data) {
                    $eventData = json_decode($data->event ?? '{}', true);

                    if (!empty($eventData['image_plate'])) {
                        $base64 = $eventData['image_plate'];
                        $src = str_contains($base64, 'base64,')
                            ? $base64
                            : 'data:image/jpeg;base64,' . $base64;

                        return $this->renderImageWithModal($src, $data->id ?? uniqid());
                    }

                    return '—';
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
                style="max-width: 150px; max-height: 80px; border-radius: 4px; cursor: pointer; object-fit: cover;"
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
        <div style="width: 150px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border: 1px dashed #dee2e6; border-radius: 4px;">
            <span style="color: #dc3545; font-size: 12px;">Ошибка загрузки</span>
        </div>
    </template>

    <template x-if="!error && !loaded">
        <div style="width: 150px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 4px;">
            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>
    </template>
</div>
HTML;
    }
}
