<?php

namespace App\MoonShine\Resources;

use App\Models\Car;
use App\Models\CarPassageEvent;
use App\Models\Stream;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\IconTextField;
use App\MoonShine\Fields\LicensePlateField;
use App\MoonShine\Fields\SelectField;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class CarEventResource extends BaseModelResource
{
    protected string $model = CarPassageEvent::class;
    protected string $title = 'Отчеты автомобилей';

    public function indexSubtitle(): ?string
    {
        return 'Распознавание ГРЗ на въезде и выезде';
    }
    protected string $column = 'id';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::DESC;
    protected bool $detailModal = true;
    protected ?string $periodColumn = 'recognized_at';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::UPDATE,
            Action::CREATE,
            Action::VIEW,
            Action::DELETE,
            Action::MASS_DELETE
        );
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with(['car.brand', 'car.color']);
    }

    public function indexFields(): iterable
    {
        return [
            LicensePlateField::make('Номер', 'plate_text'),

            AvatarField::make('Марка', 'car_id')
                ->hideAvatar()
                ->text(fn($item) => $item->car?->brand?->name ?? '—')
                ->subtext(fn($item) => $item->car?->color?->name),

            Date::make('Дата и время', 'recognized_at')
                ->withTime()
                ->sortable(),

            IconTextField::make('Камера', 'camera_id')
                ->text(fn($item) => $this->streamNameByUid($item->camera_id) === '—'
                    ? 'Камера удалена'
                    : $this->streamNameByUid($item->camera_id))
                ->deleted(fn($item) => $this->streamNameByUid($item->camera_id) === '—'),


            ColoredSelectField::make('Статус', 'car_id', fn($item) => match (true) {
                empty($item->plate_text) => 'not_recognized',
                $item->car_id !== null   => 'in_db',
                default                  => 'not_in_db',
            })->options([
                'in_db'          => ['label' => 'В базе',       'color' => 'green'],
                'not_in_db'      => ['label' => 'Не в базе',    'color' => 'yellow'],
                'not_recognized' => ['label' => 'Не распознан', 'color' => 'pink'],
            ]),

            Preview::make('Фото авто', 'image_path')
                ->changeFill(function ($item) {
                    $url = $item->imageUrl();
                    return $url ? $this->renderImageWithModal($url, 'img-' . $item->id) : '—';
                }),

            Preview::make('Фото номера', 'plate_image_path')
                ->changeFill(function ($item) {
                    $url = $item->plateImageUrl();
                    return $url ? $this->renderImageWithModal($url, 'plate-' . $item->id) : '—';
                }),
        ];
    }

    public function detailModalData(Model $item): array
    {
        /** @var CarPassageEvent $item */
        $statusKey = match (true) {
            empty($item->plate_text) => 'not_recognized',
            $item->car_id !== null   => 'in_db',
            default                  => 'not_in_db',
        };
        $statusOpt = [
            'in_db'          => ['label' => 'В базе',       'tone' => 'green'],
            'not_in_db'      => ['label' => 'Не в базе',    'tone' => 'yellow'],
            'not_recognized' => ['label' => 'Не распознан', 'tone' => 'pink'],
        ][$statusKey];

        $car   = $item->car;
        $brand = $car?->brand?->name;
        $color = $car?->color?->name;
        $people = ($car && $car->people->isNotEmpty()) ? $car->people : collect();
        $person = $people->isNotEmpty()
            ? $people->map(fn($p) => $p->getFullName())->implode(', ')
            : null;

        $serial = collect((array) $item->controllers)
            ->map(fn($c) => is_array($c)
                ? ($c['name'] ?? $c['driver'] ?? $c['actuator_device_id'] ?? null)
                : $c)
            ->filter()
            ->implode(', ') ?: null;

        $rows = [
            ['k' => 'Дата, время', 'v' => $item->recognized_at ? $item->recognized_at->format('d.m.Y, H:i:s') : '—', 'mono' => true],
            ['k' => 'ГРЗ',         'v' => $item->plate_text ?: '—', 'mono' => true],
            ['k' => 'Марка / цвет', 'v' => trim(($brand ?? '—') . ($color ? ' · ' . $color : ''))],
            ['k' => 'Статус',      'badge' => $statusOpt],
            ['k' => 'Камера',      'v' => $this->streamNameByUid($item->camera_id)],
        ];
        if ($person) {
            $row = ['k' => 'Персона', 'v' => $person];
            if ($people->count() === 1) {
                $row['link'] = $this->filteredIndexUrl(app(PersonResource::class), ['last_name' => $people->first()->last_name]);
            }
            $rows[] = $row;
        }
        if ($serial) {
            $rows[] = ['k' => 'Оборудование', 'v' => $serial, 'mono' => true];
        }

        return [
            'title'   => 'Событие · распознавание ГРЗ',
            'layout'  => 'gallery',
            'gallery' => [
                ['label' => 'Кадр автомобиля', 'image' => $item->imageUrl()],
                ['label' => 'Фото номера',     'image' => $item->plateImageUrl(), 'plate' => $item->plate_text],
            ],
            'rows'    => $rows,
        ];
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend($this->getIdentificationButton());
    }

    protected function getIdentificationButton(): ActionButton
    {
        return ActionButton::make(
            '',
            fn($item) => $this->getAsyncMethodUrl(
                method: 'getIdentifyForm',
                params: [
                    'item_id' => $item->getKey(),
                    '_component_name' => $this->getListComponentName(),
                    '_async_form' => true,
                ]
            )
        )
            ->icon($this->iconFile('btn-identify-car'), true)
            ->class(['js-identify-car-button', 'ds-rowbtn'])
            ->customAttributes([
                '@click.prevent.stop' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Идентификация автомобиля' })",
            ])
            ->async(selector: "#{$this->safeModalName}_content")
            ->canSee(fn($item) => $item->car_id === null && ! empty($item->plate_text));
    }

    public function getIdentifyForm(MoonShineRequest $request): string
    {
        $itemId = $request->get('item_id');
        $item = CarPassageEvent::find($itemId);

        if (! $item) {
            return "Запись #{$itemId} не найдена в базе данных";
        }

        return FormBuilder::make()
            ->action($this->getAsyncMethodUrl('saveCar', null, ['item_id' => $item->getKey()]))
            ->async()
            ->fields([...app(CarResource::class)->formFields()])
            ->fill(['license_plate' => $item->plate_text])
            ->submit('Сохранить', ['class' => 'btn-primary'])
            ->render();
    }

    public function saveCar(MoonShineRequest $request)
    {
        $request->merge([
            'license_plate' => mb_strtoupper(preg_replace('/\s+/u', '', (string) $request->input('license_plate')) ?? ''),
        ]);

        $carResource = app(CarResource::class);

        $data = $request->validate($carResource->rules(null));

        $car = Car::query()->create([
            'license_plate'   => $data['license_plate'],
            'brand_id'        => $data['brand_id'] ?? null,
            'color_id'        => $data['color_id'] ?? null,
            'organization_id' => $data['organization_id'] ?? null,
            'comment'         => $data['comment'] ?? null,
            'source'          => Car::SOURCE_MANUAL,
        ]);

        $car->people()->sync($request->input('people_ids', []));
        $car->tags()->sync($request->input('tags', []));
        $car->rules()->sync($request->input('rule_ids', []));

        $carResource->syncCarWithPingateControllers($car);

        return MoonShineJsonResponse::make()
            ->toast('Автомобиль добавлен в базу!', ToastType::SUCCESS)
            ->redirect($this->getUrl());
    }

    public function search(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Период отчётности', 'recognized_at')
                ->withTime(),

            SelectField::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable(),

            SelectField::make('Статус', 'db_status_filter')
                ->options([
                    'in_db'          => 'В базе',
                    'not_in_db'      => 'Не в базе',
                    'not_recognized' => 'Не распознан',
                ])
                ->placeholder('Статус')
                ->nullable()
                ->onApply(function (\Illuminate\Database\Eloquent\Builder $query, $value) {
                    return match ($value) {
                        'in_db'          => $query->whereNotNull('car_id'),
                        'not_in_db'      => $query->whereNull('car_id')->whereNotNull('plate_text')->where('plate_text', '!=', ''),
                        'not_recognized' => $query->where(fn($q) => $q->whereNull('plate_text')->orWhere('plate_text', '')),
                        default          => $query,
                    };
                }),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата и время', 'recognized_at')
                ->format('d.m.Y H:i:s'),

            Text::make('Камера', 'camera_id')
                ->changeFill(fn($item) => $this->streamNameByUid($item->camera_id)),

            Text::make('Номер', 'plate_text'),

            Text::make('Статус', 'car_id')
                ->changeFill(fn($item) => match (true) {
                    empty($item->plate_text) => 'Не распознан',
                    $item->car_id !== null   => 'В базе',
                    default                  => 'Не в базе',
                }),

        ];
    }

    private array $streamCache = [];
    private array $streamLocCache = [];

    private function streamNameByUid(?string $uid): string
    {
        if (empty($uid)) {
            return '—';
        }

        if (! array_key_exists($uid, $this->streamCache)) {
            $this->streamCache[$uid] = Stream::withTrashed()->where('uid', $uid)->value('name');
        }

        return $this->streamCache[$uid] ?? $uid;
    }

    private function streamLocationByUid(?string $uid): ?string
    {
        if (empty($uid)) {
            return null;
        }

        if (! array_key_exists($uid, $this->streamLocCache)) {
            $this->streamLocCache[$uid] = Stream::withTrashed()->where('uid', $uid)->value('location');
        }

        $location = $this->streamLocCache[$uid];

        return $location ? \Illuminate\Support\Str::limit($location, 26) : null;
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
                @click.stop="open = true"
                title="Нажмите для увеличения"
                loading="lazy"
            />

            <div x-show="open" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 x-transition.opacity @click.self.stop="open = false" @keydown.escape.window="open = false" x-cloak>
                <div style="position: relative;">
                    <img src="$src" style="max-width: 90vw; max-height: 90vh; border-radius: 8px;">
                    <button @click.stop="open = false" style="position: absolute; top: -10px; right: -10px; background: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 20px;">×</button>
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
