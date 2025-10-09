<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Database\Eloquent\Model;
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

    protected function modifyDetailButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->canSee(fn() => false);
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('Ключ', 'key')
                ->sortable(),
            Select::make('Тип ключа', 'type')
                ->options([
                    'Mifare' => 'Mifare',
                ])
                ->sortable(),
            Select::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->searchable()
                ->sortable()
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Ключ', 'key')
                ->required(),
            Select::make('Тип ключа', 'type')
                ->options([
                    'Mifare' => 'Mifare',
                ])
                ->required()
                ->default('Mifare'),
            Select::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->searchable()
                ->required()
                ->default(request()->person_id??null),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            Text::make('Ключ', 'key'),
            Select::make('Тип ключа', 'type')
                ->options([
                    'Mifare' => 'Mifare',
                ]),
            Select::make('Персона', 'person_id')
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
            Select::make('Тип ключа', 'type')
                ->placeholder('Фильтрация по типу ключа')
                ->options([
                    'Mifare' => 'Mifare',
                ]),
            Select::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->searchable()
                ->placeholder('Фильтрация по имени')
                ->nullable(),
        ];
    }

}
