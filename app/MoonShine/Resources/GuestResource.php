<?php

namespace App\MoonShine\Resources;

use App\Models\Guest;
use App\MoonShine\Fields\PhotoField;
use Dflydev\DotAccessData\Data;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\ListOf;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Phone;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Textarea;
use Illuminate\Support\Facades\Storage;

class GuestResource extends BaseModelResource
{
    protected string $model = Guest::class;
    protected string $title = 'Гости';
    protected string $column = 'full_name';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW);
    }


    public function indexFields(): iterable
    {
        return [
            Text::make('Внешний ID', 'external_id')
                ->sortable(),
            Text::make('ФИО', 'full_name')
                ->sortable(),
            Text::make('Телефон', 'phone')
                ->sortable(),
            Image::make('Фото', 'photo')
                ->multiple(),
            Text::make('Документ', 'document')
                ->sortable(),
            Text::make('Комментарий', 'comment')
                ->sortable(),
            Date::make('Действует с', 'entry_start')
                ->sortable(),
            Date::make('Действует до', 'entry_end')
                ->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Внешний ID', 'external_id')
                ->nullable(),
            Text::make('ФИО', 'full_name')
                ->required(),
            Phone::make('Телефон', 'phone')
                ->nullable()
                ->mask('+7 (999) 999-99-99'),
            PhotoField::make('Фото', 'photo')
                ->multiple()
                ->removable()
                ->dir('person/photos')
                ->allowedExtensions(['jpg', 'png', 'jpeg', 'webp'])
                ->onApply(function ($data): mixed {
                    return $data;
                })
                ->onAfterApply(function ($data, false|array $values, Image $field) {
                    $remainingValues = $field->getRemainingValues() ?? [];

                    if ($remainingValues instanceof \Illuminate\Support\Collection) {
                        $remainingValues = $remainingValues->toArray();
                    }

                    if ($values !== false) {
                        foreach ($values as $value) {
                            if ($value instanceof \Illuminate\Http\UploadedFile) {
                                $path = $value->store($field->getDir(), 'public');
                                $remainingValues[] = $path;
                            }
                        }
                    }

                    $data->update(['photo' => array_values($remainingValues)]);
                    return $data;
                })
                ->onAfterDestroy(function ($data, mixed $values, Image $field) {
                    if (is_array($values)) {
                        foreach ($values as $value) {
                            Storage::disk('public')->delete($value);
                        }
                    }
                    return $data;
                }),
            Text::make('Документ', 'document')
                ->nullable(),
            Textarea::make('Комментарий', 'comment')
                ->nullable(),
            Date::make('Действует с', 'entry_start')
                ->nullable()
                ->withTime(),
            Date::make('Действует до', 'entry_end')
                ->nullable()
                ->withTime(),
        ];
    }

    public function rules($item): array
    {
        return [
            'external_id' => ['nullable', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'array'],
            'photo.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'document' => ['nullable', 'string', 'max:100'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function search(): array
    {
        return [
            'full_name',
            'phone',
            'external_id',
            'document',
            'comment'
        ];
    }

    public function filters(): array
    {
        return [
            Text::make('ФИО', 'full_name')
                ->placeholder('Фильтрация по ФИО'),

            Text::make('Телефон', 'phone')
                ->placeholder('Фильтрация по телефону'),

            Text::make('Внешний ID', 'external_id')
                ->placeholder('Фильтрация по внешнему ID'),

            Text::make('Документ', 'document')
                ->placeholder('Фильтрация по документу'),

            Text::make('Комментарий', 'comment')
                ->placeholder('Фильтрация по комментарию'),
        ];
    }

    public function validationMessages(): array
    {
        return [
            'full_name.required' => 'Поле "ФИО" обязательно для заполнения',
            'photo.*.image' => 'Недопустимый формат файла',
            'photo.*.mimes' => 'Допустимые форматы: JPG, JPEG, PNG, WEBP',
            'photo.*.max' => 'Размер фотографии не должен превышать 5 МБ',
        ];
    }

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->withCount('visits');
    }
}
