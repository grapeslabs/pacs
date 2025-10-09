<?php

namespace App\MoonShine\Resources;

use App\Models\Reference;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Json;

class ReferenceResource extends BaseModelResource
{
    protected string $model = Reference::class;
    protected string $title = 'Все справочники';
    protected string $column = 'name';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Text::make('Название', 'name')
                ->sortable(),

            Text::make('Код', 'code')
                ->sortable(),

            Select::make('Тип', 'type')
                ->options([
                    Reference::TYPE_STATUS => 'Статусы',
                    Reference::TYPE_CATEGORY => 'Категории',
                    Reference::TYPE_TYPE => 'Типы',
                    Reference::TYPE_UNIT => 'Единицы измерения',
                    Reference::TYPE_OTHER => 'Прочие',
                ])
                ->sortable(),

            Checkbox::make('Активен', 'is_active')
                ->sortable(),

            Number::make('Порядок', 'sort_order')
                ->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),

            Text::make('Название', 'name')
                ->required(),

            Text::make('Код', 'code')
                ->required(),

            Select::make('Тип справочника', 'type')
                ->options([
                    Reference::TYPE_STATUS => 'Статусы',
                    Reference::TYPE_CATEGORY => 'Категории',
                    Reference::TYPE_TYPE => 'Типы',
                    Reference::TYPE_UNIT => 'Единицы измерения',
                    Reference::TYPE_OTHER => 'Прочие',
                ])
                ->required(),

            Textarea::make('Описание', 'description')
                ->nullable(),

            Json::make('Данные', 'data')
                ->keyValue('Ключ', 'Значение')
                ->nullable(),

            Checkbox::make('Активен', 'is_active')
                ->default(true),

            Number::make('Порядок сортировки', 'sort_order')
                ->default(0),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
            Text::make('Код', 'code'),
            Text::make('Тип справочника', 'type')
                ->setValue(fn($item) => $item->type_label),
            Textarea::make('Описание', 'description'),
            Json::make('Данные', 'data')
                ->keyValue('Ключ', 'Значение'),
            Checkbox::make('Активен', 'is_active')
                ->setValue(fn($item) => $item->status_label),
            Number::make('Порядок сортировки', 'sort_order'),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:references,code,' . ($item?->id ?? 0)],
            'type' => ['required', 'string', 'in:status,category,type,unit,other'],
            'description' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    public function search(): array
    {
        return [
            'name',
            'code',
            'description'
        ];
    }

    public function filters(): array
    {
        return [
            Select::make('Тип', 'type')
                ->options([
                    Reference::TYPE_STATUS => 'Статусы',
                    Reference::TYPE_CATEGORY => 'Категории',
                    Reference::TYPE_TYPE => 'Типы',
                    Reference::TYPE_UNIT => 'Единицы измерения',
                    Reference::TYPE_OTHER => 'Прочие',
                ])
                ->nullable(),

            Checkbox::make('Только активные', 'is_active')
                ->onValue(1)
                ->offValue(0),
        ];
    }
}
