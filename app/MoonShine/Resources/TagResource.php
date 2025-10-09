<?php

namespace App\MoonShine\Resources;

use App\Models\Tag;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class TagResource extends BaseModelResource
{
    protected string $model = Tag::class;

    protected string $title = 'Теги';
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
            Text::make('Сокращение', 'short_name')->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')
                ->required(),
            Text::make('Сокращение', 'short_name'),
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
