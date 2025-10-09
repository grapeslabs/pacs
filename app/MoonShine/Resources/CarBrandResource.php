<?php

namespace App\MoonShine\Resources;

use App\Models\CarBrand;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;

class CarBrandResource extends BaseModelResource implements HasImportExportContract
{
    use ImportExportConcern;
    protected string $model = CarBrand::class;
    protected string $title = 'Марки автомобилей';
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
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
        ];
    }

    protected function export(): ?Handler
    {
        return null;
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')->required(),
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
