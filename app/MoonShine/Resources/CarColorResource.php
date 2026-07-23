<?php

namespace App\MoonShine\Resources;
use App\MoonShine\Fields\ColorSwatchField;
use App\MoonShine\Fields\CountField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Database\Eloquent\Model;
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

class CarColorResource extends BaseModelResource
{
    protected string $model = CarColor::class;
    protected string $title = 'Цвета автомобилей';
    protected ?string $editModalTitle = 'Редактирование цвета';
    protected string $column = 'name';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function importFields():iterable
    {
        return [
            Text::make('name'),
        ];
    }
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
            ColorSwatchField::make('Название', 'name')->sortable(),
            CountField::make('Автомобилей', 'id', fn($item) => $item->cars()->count()),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            CustomText::make('Название', 'name')->required(),
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
            SelectField::make('Цвет', 'id')
                ->options(CarColor::query()->get()->pluck('name', 'id')->toArray())
                ->multiple()
                ->placeholder('Фильтрация по названию')
                ->nullable()
        ];
    }
}
