<?php

namespace App\MoonShine\Resources;

use App\Models\Car;
use App\Models\CarPassageEvent;
use App\Models\Passage;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\SelectField;
use Illuminate\Contracts\Database\Eloquent\Builder;
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

class PassageEventResource extends BaseModelResource
{
    protected string $model = CarPassageEvent::class;
    protected string $title = 'Отчёты проезда';

    public function indexSubtitle(): ?string
    {
        return 'Проезды автомобилей через шлагбаумы';
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
            Action::MASS_DELETE,
        );
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder
            ->whereNotNull('passage_id')
            ->with(['passage', 'car', 'rule']);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Date::make('Дата и время', 'recognized_at')
                ->withTime()
                ->sortable(),

            Text::make('Проезд', 'passage', fn($item) => $item->passage?->name ?? '—'),

            ColoredSelectField::make('Направление', 'direction')
                ->options([
                    'entry' => ['label' => 'Въезд',  'color' => 'green'],
                    'exit'  => ['label' => 'Выезд',  'color' => 'blue'],
                ]),

            ColoredSelectField::make('Тип события', 'status')
                ->options([
                    'allowed' => ['label' => 'Проезд разрешён', 'color' => 'green'],
                    'allowed_inactive' => ['label' => 'Разрешён, устройство неактивно', 'color' => 'gray'],
                    'allowed_no_link' => ['label' => 'Разрешён, нет связи', 'color' => 'orange'],
                    'allowed_offline' => ['label' => 'Разрешён, отвал устройства', 'color' => 'red'],
                    'denied' => ['label' => 'Проезд запрещён', 'color' => 'red'],
                    'in_db' => ['label' => 'В базе',          'color' => 'blue'],
                    'not_in_db' => ['label' => 'Не в базе',       'color' => 'yellow'],
                    'not_recognized' => ['label' => 'Не распознан',    'color' => 'pink'],
                ]),

            Text::make('ГРЗ', 'plate_text', fn($item) => $item->plate_text
                ? ($item->car?->license_plate ? $item->car->license_plate : $item->plate_text)
                : '—'),

            Preview::make('Фото номера', 'plate_image_path')
                ->changeFill(function ($item) {
                    $url = $item->plateImageUrl();
                    return $url ? $this->renderImageWithModal($url, 'plate-' . $item->id) : '—';
                }),

            Text::make('Правило', 'rule_name', fn($item) => $item->rule_name ?: '—'),
        ];
    }

    public function detailModalData(\Illuminate\Database\Eloquent\Model $item): array
    {
        /** @var CarPassageEvent $item */
        $statusColors = [
            'allowed'          => 'green',
            'allowed_inactive' => 'gray',
            'allowed_no_link'  => 'orange',
            'allowed_offline'  => 'red',
            'denied'           => 'red',
            'in_db'            => 'blue',
            'not_in_db'        => 'yellow',
            'not_recognized'   => 'pink',
        ];

        $car    = $item->car;
        $brand  = $car?->brand?->name;
        $color  = $car?->color?->name;
        $people = ($car && $car->people->isNotEmpty()) ? $car->people : collect();
        $person = $people->isNotEmpty()
            ? $people->map(fn($p) => $p->getFullName())->implode(', ')
            : null;

        $plate = $item->plate_text
            ? ($car?->license_plate ?: $item->plate_text)
            : null;

        $rows = [
            ['k' => 'Дата и время', 'v' => $item->recognized_at ? $item->recognized_at->format('d.m.Y, H:i:s') : '—', 'mono' => true],
            ['k' => 'Проезд',       'v' => $item->passage?->name ?? '—'],
            ['k' => 'Направление',  'badge' => $item->direction === 'exit'
                ? ['label' => 'Выезд', 'tone' => 'blue']
                : ['label' => 'Въезд', 'tone' => 'green']],
        ];
        if ($item->status && isset(CarPassageEvent::STATUSES[$item->status])) {
            $rows[] = ['k' => 'Тип события', 'badge' => [
                'label' => CarPassageEvent::STATUSES[$item->status],
                'tone'  => $statusColors[$item->status] ?? 'gray',
            ]];
        }
        $rows[] = ['k' => 'ГРЗ', 'v' => $plate ?: '—', 'mono' => true];
        if ($brand || $color) {
            $rows[] = ['k' => 'Марка / цвет', 'v' => trim(($brand ?? '—') . ($color ? ' · ' . $color : ''))];
        }
        if ($person) {
            $row = ['k' => 'Персона', 'v' => $person];
            if ($people->count() === 1) {
                $row['link'] = $this->filteredIndexUrl(app(PersonResource::class), ['last_name' => $people->first()->last_name]);
            }
            $rows[] = $row;
        }
        $rows[] = ['k' => 'Правило', 'v' => $item->rule_name ?: '—'];

        return [
            'title'   => 'Событие проезда',
            'layout'  => 'gallery',
            'gallery' => [
                ['label' => 'Кадр автомобиля', 'image' => $item->imageUrl()],
                ['label' => 'Фото номера',     'image' => $item->plateImageUrl(), 'plate' => $plate],
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
            ->icon(file_get_contents(public_path('icons/btn-identify-car.svg')), true)
            ->class('js-identify-car-button')
            ->customAttributes([
                '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Идентификация автомобиля' })",
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
            'license_plate' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('license_plate'))),
        ]);

        $data = $request->validate(app(CarResource::class)->rules(null));

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
            DateRange::make('Период', 'recognized_at')->withTime(),

            SelectField::make('Проезд', 'passage_id')
                ->nullable()
                ->options(Passage::query()->pluck('name', 'id')->toArray()),

            SelectField::make('Направление', 'direction')
                ->nullable()
                ->options(['entry' => 'Въезд', 'exit' => 'Выезд']),

            SelectField::make('Тип события', 'status')
                ->nullable()
                ->options(CarPassageEvent::STATUSES),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата и время', 'recognized_at')->format('d.m.Y H:i:s'),

            Text::make('Проезд', 'passage_id')
                ->changeFill(fn($item) => $item->passage?->name ?? ''),

            Text::make('Направление', 'direction')
                ->changeFill(fn($item) => match ($item->direction) {
                    'entry' => 'Въезд',
                    'exit'  => 'Выезд',
                    default => '',
                }),

            Text::make('Тип события', 'status')
                ->changeFill(fn($item) => CarPassageEvent::STATUSES[$item->status] ?? ''),

            Text::make('ГРЗ', 'plate_text'),

            Text::make('Правило', 'rule_name'),
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
            <img src="$src" style="max-width: 150px; max-height: 80px; border-radius: 4px; cursor: pointer; object-fit: cover;"
                @click="open = true" loading="lazy"/>
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
            <span style="font-size: 12px; color: #6c757d;">Загрузка...</span>
        </div>
    </template>
</div>
HTML;
    }
}
