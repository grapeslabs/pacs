<?php

namespace App\MoonShine\Resources;

use App\Models\Organization;
use App\MoonShine\Fields\DadataOrganizationField;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Contracts\UI\ActionButtonContract;

class OrganizationResource extends BaseModelResource
{
    protected string $model = Organization::class;

    protected string $title = 'Организации';
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

    public function formFields(): iterable
    {
        return [
            DadataOrganizationField::make('Поиск организации', 'search_query'),
            Text::make('ИНН', 'inn')->mask('999999999999'),
            Text::make('Название полное', 'full_name')->required(),
            Text::make('Название сокращенное', 'short_name')->required(),
            Text::make('Адрес', 'address'),
            Text::make('Контактные данные', 'contact_data'),
            Textarea::make('Комментарий', 'comment'),
        ];
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('ИНН', 'inn')->sortable(),
            Text::make('Название полное', 'full_name')->sortable(),
            Text::make('Название сокращенное', 'short_name')->sortable(),
            Text::make('Адрес', 'address')->sortable(),
            Text::make('Контактные данные', 'contact_data')->sortable(),
            Textarea::make('Комментарий', 'comment')->sortable(),
        ];
    }

    public function rules($item): array
    {
        return [
            'inn' => ['nullable', 'string', 'max:12', 'unique:organizations,inn,' . ($item?->id ?? 0)],
            'full_name' => ['required', 'string'],
            'short_name' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'contact_data' => ['nullable', 'string'],
        ];
    }

    public function search(): array
    {
        return [
            'id',
            'inn',
            'full_name',
            'short_name',
            'comment',
            'address',
            'contact_data',
        ];
    }

    public function filters(): array
    {
        return [
            Text::make('ИНН', 'inn')
                ->placeholder('Фильтрация по ИНН'),

            Text::make('Название полное', 'full_name')
                ->placeholder('Фильтрация по полному названию'),

            Text::make('Название сокращенное', 'short_name')
                ->placeholder('Фильтрация по сокращенному названию'),

            Text::make('Адрес', 'address')
                ->placeholder('Фильтрация по адресу'),

            Text::make('Контактные данные', 'contact_data')
                ->placeholder('Фильтрация по контактным данным'),

            Textarea::make('Комментарий', 'comment')
                ->placeholder('Фильтрация по комментарию'),
        ];
    }
}
