<?php

namespace App\MoonShine\Resources;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Model;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\PhotoField;
use Carbon\Carbon;
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
use App\MoonShine\Fields\CustomTextarea;
use Illuminate\Support\Facades\Storage;

class GuestResource extends BaseModelResource
{
    protected string $model = Guest::class;
    protected string $title = 'Гости';
    protected string $column = 'full_name';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить гостя';

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function detailSidebarData(Model $item): array
    {
        $icPerson = '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
        $icCheck = '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6z" stroke="currentColor" stroke-width="1.6"/><path d="m9 11.5 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $photos = is_array($item->photo) ? $item->photo : [];
        $avatar = ! empty($photos[0]) ? Storage::disk('public')->url($photos[0]) : null;
        $initials = collect(preg_split('/\s+/u', trim($item->full_name ?? '')) ?: [])
            ->take(2)
            ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->join('');

        $now = now();
        $start = $item->entry_start;
        $end = $item->entry_end;
        if ($start && $now->lt($start)) {
            $badge = ['text' => 'Ожидает', 'tone' => 'muted', 'icon' => $icCheck];
        } elseif ($end && $now->gt($end)) {
            $badge = ['text' => 'Истёк', 'tone' => 'danger', 'icon' => $icCheck];
        } else {
            $badge = ['text' => 'Действует', 'icon' => $icCheck];
        }

        $period = collect([$start?->format('d.m.Y'), $end?->format('d.m.Y')])->filter()->join(' — ');

        $sections = [
            [
                'icon' => $icPerson,
                'title' => 'Данные гостя',
                'fields' => [
                    ['label' => 'Телефон', 'value' => $item->phone],
                    ['label' => 'Документ', 'value' => $item->document],
                    ['label' => 'Действует', 'value' => $period],
                ],
            ],
        ];

        if (! blank($item->comment)) {
            $sections[] = [
                'icon' => $icPerson,
                'title' => 'Комментарий',
                'text' => $item->comment,
            ];
        }

        $sub = collect(['Гостевой пропуск', $item->external_id])->filter()->join(' · ');

        return [
            'title' => 'Карточка гостя',
            'subtitle' => $sub,
            'hero' => [
                'title' => $item->full_name,
                'subtitle' => $sub,
                'avatar' => $avatar,
                'initials' => $avatar ? null : $initials,
                'badges' => [$badge],
            ],
            'sections' => $sections,
            'footer' => [
                ['spacer' => true],
                ['html' => $this->detailEditButtonHtml($item)],
            ],
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW);
    }


    public function indexFields(): iterable
    {
        return [
            AvatarField::make('ФИО', 'full_name')
                ->text('full_name')
                ->subtext('external_id')
                ->photo(fn ($item) => is_array($item->photo) ? ($item->photo[0] ?? null) : $item->photo)
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
            CustomText::make('Внешний ID', 'external_id')
                ->unique('guests','external_id')
                ->nullable(),
            CustomText::make('ФИО', 'full_name')
                ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                ->required(),
            CustomText::make('Телефон', 'phone')
                ->nullable()
                ->phone(),
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
            CustomText::make('Документ', 'document')
                ->nullable(),
            CustomTextarea::make('Комментарий', 'comment')
                ->nullable(),
            CustomDate::make('Действует с', 'entry_start')
                ->after(Carbon::now(), 'Начало действия не может в прошлом'),
            CustomDate::make('Действует до', 'entry_end')
                ->afterField('entry_start', 'Конец действия не может быть раньше его начала'),
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
