<?php

namespace App\MoonShine\Resources;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use App\Models\CarColor;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class CarColorResource extends BaseModelResource implements HasImportExportContract
{
    protected string $model = CarColor::class;
    protected string $title = 'Цвета автомобилей';
    protected string $column = 'name';
    use ImportExportConcern;
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function export()
    {
        return false;
    }

    protected function importFields():iterable
    {
        return [
            Text::make('name'),
        ];
    }
    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')->required(),
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function search(): array
    {
        return  ['name'];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Цвет', 'id')
                ->options(CarColor::query()->get()->pluck('name', 'id')->toArray())
                ->multiple()
                ->placeholder('Фильтрация по названию')
                ->searchable(false)
                ->nullable()
        ];
    }
}
