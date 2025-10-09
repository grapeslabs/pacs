<?php

namespace App\MoonShine\Resources;

use App\Models\Equipment;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Json;

class EquipmentResource extends BaseModelResource
{
    protected string $model = Equipment::class;
    protected string $title = 'Все оборудование';
    protected string $column = 'name';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function menuGroup(): string
    {
        return 'Оборудование';
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Text::make('Название', 'name')
                ->sortable(),

            Select::make('Тип', 'type')
                ->options([
                    Equipment::TYPE_TERMINAL => 'Терминалы доступа',
                    Equipment::TYPE_BARRIER => 'Шлагбаумы',
                    Equipment::TYPE_CONTROLLER => 'Контроллеры СКУД',
                ])
                ->sortable(),

            Text::make('Модель', 'model')
                ->sortable(),

            Text::make('Серийный номер', 'serial_number')
                ->sortable(),

            Select::make('Статус', 'status')
                ->options([
                    Equipment::STATUS_ACTIVE => 'Активен',
                    Equipment::STATUS_INACTIVE => 'Неактивен',
                    Equipment::STATUS_MAINTENANCE => 'На обслуживании',
                ])
                ->sortable(),

            Text::make('Местоположение', 'location')
                ->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),

            Text::make('Название', 'name')
                ->required(),

            Select::make('Тип оборудования', 'type')
                ->options([
                    Equipment::TYPE_TERMINAL => 'Терминал доступа',
                    Equipment::TYPE_BARRIER => 'Шлагбаум',
                    Equipment::TYPE_CONTROLLER => 'Контроллер СКУД',
                ])
                ->required(),

            Text::make('Модель', 'model')
                ->nullable(),

            Text::make('Серийный номер', 'serial_number')
                ->nullable(),

            Select::make('Статус', 'status')
                ->options([
                    Equipment::STATUS_ACTIVE => 'Активен',
                    Equipment::STATUS_INACTIVE => 'Неактивен',
                    Equipment::STATUS_MAINTENANCE => 'На обслуживании',
                ])
                ->default(Equipment::STATUS_ACTIVE),

            Text::make('Местоположение', 'location')
                ->nullable(),

            Textarea::make('Описание', 'description')
                ->nullable(),

            Json::make('Характеристики', 'specifications')
                ->keyValue('Параметр', 'Значение')
                ->nullable(),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
            Text::make('Тип оборудования', 'type')
                ->setValue(fn($item) => $item->type_label),
            Text::make('Модель', 'model'),
            Text::make('Серийный номер', 'serial_number'),
            Text::make('Статус', 'status')
                ->setValue(fn($item) => $item->status_label),
            Text::make('Местоположение', 'location'),
            Textarea::make('Описание', 'description'),
            Json::make('Характеристики', 'specifications')
                ->keyValue('Параметр', 'Значение'),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:terminal,barrier,controller'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,inactive,maintenance'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'array'],
        ];
    }

    public function search(): array
    {
        return [
            'name',
            'model',
            'serial_number',
            'location',
            'description'
        ];
    }

    public function filters(): array
    {
        return [
            Select::make('Тип', 'type')
                ->options([
                    Equipment::TYPE_TERMINAL => 'Терминалы доступа',
                    Equipment::TYPE_BARRIER => 'Шлагбаумы',
                    Equipment::TYPE_CONTROLLER => 'Контроллеры СКУД',
                ])
                ->nullable(),

            Select::make('Статус', 'status')
                ->options([
                    Equipment::STATUS_ACTIVE => 'Активен',
                    Equipment::STATUS_INACTIVE => 'Неактивен',
                    Equipment::STATUS_MAINTENANCE => 'На обслуживании',
                ])
                ->nullable(),

            Text::make('Модель', 'model'),

            Text::make('Серийный номер', 'serial_number'),
        ];
    }

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->with([]);
    }
}
