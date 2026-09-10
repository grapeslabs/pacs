<?php

namespace App\MoonShine\Resources;

use App\Models\CarBrand;
use App\MoonShine\Fields\CountField;
use App\MoonShine\Fields\CustomText;
use Illuminate\Database\Eloquent\Model;
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

    public function deleteConfirmation(Model $item): array
    {
        return [
            'title' => 'Удалить запись?',
            'text'  => '«' . ($item->name ?: 'Запись') . '» будет удалена из справочника.',
        ];
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('Название', 'name')->sortable(),
            CountField::make('Автомобилей', 'id', fn($item) => $item->cars()->count()),
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
