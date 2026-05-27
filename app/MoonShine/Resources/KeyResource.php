<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\Contracts\UI\ActionButtonContract;

class KeyResource extends BaseModelResource
{
    protected string $model = Key::class;

    protected string $title = 'Ключи';
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
            Text::make('Ключ', 'key')
                ->sortable(),
            Select::make('Тип ключа', 'type')
                ->options(Key::TYPES)
                ->sortable(),
            BelongsTo::make('Персона', 'person', fn($item) => trim(($item->last_name ?? '') . ' ' . ($item->first_name ?? '')), resource: PersonResource::class)
                ->sortable()
        ];
    }

    public function formFields(): iterable
    {
        return [
            CustomText::make('Ключ', 'key')
                ->unique('keys', 'key', 'Ключ должен быть уникальным')
                ->required(),
            SelectField::make('Тип ключа', 'type')
                ->options(Key::TYPES)
                ->required()
                ->default('Mifare'),
            SelectField::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->required()
                ->default(request()->person_id??null),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            Text::make('Ключ', 'key'),
            SelectField::make('Тип ключа', 'type')
                ->options([
                    'Mifare' => 'Mifare',
                ]),
            SelectField::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray()),
        ];
    }

    public function search(): array
    {
        return [
            'id',
            'key',
            'type',
            'person.last_name',
        ];
    }

    public function filters(): array
    {
        return [
            Text::make('Ключ', 'key')
            ->placeholder('Фильтрация по имени ключа'),
            SelectField::make('Тип ключа', 'type')
                ->placeholder('Фильтрация по типу ключа')
                ->options([
                    'Mifare' => 'Mifare',
                ]),
            SelectField::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->placeholder('Фильтрация по имени')
                ->nullable(),
        ];
    }

}
