<?php

namespace App\MoonShine\Resources;

use App\Models\CarBrand;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;

class CarBrandResource extends BaseModelResource
{
    protected string $model = CarBrand::class;
    protected string $title = 'Марки автомобилей';
    protected ?string $editModalTitle = 'Редактирование марки';
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
            Text::make('Название', 'name')->sortable(),
            \App\MoonShine\Fields\CountField::make('Автомобилей', 'id', fn($item) => $item->cars()->count()),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            CustomText::make('Название', 'name')->required()->unique('car_brands', 'name'),
        ];
    }

    protected function importFields(): iterable
    {
        return [
            Text::make('Name'),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            Text::make('Название', 'name'),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function filters(): iterable
    {
        return [
            Text::make('Название', 'name'),
        ];
    }

    public function search():array
    {
        return ['name'];
    }
}
