<?php

namespace App\MoonShine\Resources;

use App\Models\CarTag;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class CarTagResource extends BaseModelResource
{
    protected string $model = CarTag::class;
    protected string $title = 'Теги автомобилей';
    protected string $column = 'name';

    protected function pages(): array
    {
        return [CustomIndexPage::class, DetailPage::class, FormPage::class];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Сокращение', 'short_name')->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            CustomText::make('Название', 'name')->required(),
            CustomText::make('Сокращение', 'short_name')->nullable(),
        ];
    }

    public function rules($item): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:car_tags,name,' . ($item?->id ?? 0)],
            'short_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function search(): array
    {
        return ['name', 'short_name'];
    }

    public function filters(): array
    {
        return [
            Text::make('Название', 'name'),
        ];
    }
}
