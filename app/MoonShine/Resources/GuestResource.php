<?php

namespace App\MoonShine\Resources;

use App\Models\Guest;
use App\Models\Organization;
use App\MoonShine\Fields\SelectField;
use Illuminate\Database\Eloquent\Model;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\ColoredSelectField;
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
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Phone;
use MoonShine\UI\Fields\Image;
use App\MoonShine\Fields\CustomTextarea;
use Illuminate\Support\Facades\Storage;
use MoonShine\UI\Components\Layout\Grid;

class GuestResource extends BaseModelResource
{
    protected string $model = Guest::class;
    protected string $title = 'Гости';

    public function indexSubtitle(): ?string
    {
        return $this->countSubtitle('временный пропуск', 'временных пропуска', 'временных пропусков');
    }
    protected ?string $editModalTitle = 'Редактирование гостя';
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
        $icPerson = @file_get_contents(public_path('icons/person.svg'))?:'';
        $icCheck = @file_get_contents(public_path('icons/check.svg'))?:'';

        $photos = is_array($item->photo) ? $item->photo : [];
        $avatar = ! empty($photos[0]) ? Storage::disk('public')->url($photos[0]) : null;
        $initials = collect(preg_split('/\s+/u', trim($item->full_name ?? '')) ?: [])
            ->take(2)
            ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->join('');

        $now = now();
        $start = $item->entry_start;
        $end = $item->entry_end;
        if ($start && $now->lt($start->copy()->startOfDay())) {
            $badge = ['text' => 'Ожидает', 'tone' => 'muted', 'icon' => $icCheck];
        } elseif ($end && $now->gt($end->copy()->endOfDay())) {
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
                'icon' => $this->commentIcon(),
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


    public function deleteConfirmation(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'title' => 'Удалить гостя?',
            'text'  => 'Пропуск «' . ($item->full_name ?: 'Гость') . '» будет удалён.',
        ];
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
            Text::make('Документ', 'document')
                ->sortable(),
            Date::make('Действует до', 'entry_end')
                ->sortable(),
            ColoredSelectField::make('Статус', 'entry_end', function ($item) {
                $now = now();
                if ($item->entry_start && $now->lt($item->entry_start->copy()->startOfDay())) {
                    return 'waiting';
                }
                if ($item->entry_end && $now->gt($item->entry_end->copy()->endOfDay())) {
                    return 'expired';
                }
                return 'active';
            })->options([
                'waiting' => ['label' => 'Ждёт', 'color' => 'yellow'],
                'active'  => ['label' => 'Действует', 'color' => 'green'],
                'expired' => ['label' => 'Истёк', 'color' => 'gray'],
            ]),
        ];
    }

    public function formFields(): iterable
    {
        $phonIcon = file_get_contents(public_path('icons/phone.svg'));
        $documentIcon = file_get_contents(public_path('icons/document.svg'));
        return [
            Grid::make([
                Column::make([
                    CustomText::make('ФИО', 'full_name')
                        ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                        ->required(),
                ])->columnSpan(6),
                Column::make([
                    CustomText::make('Телефон', 'phone')
                        ->nullable()
                        ->phone()
                        ->leadingIcon($phonIcon),
                    ])->columnSpan(6),
            ]),
            CustomText::make('Внешний ID', 'external_id')
                ->unique('guests','external_id')
                ->nullable(),
            CustomText::make('Документ', 'document')
                ->nullable()
                ->leadingIcon($documentIcon),
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
            Grid::make([
                Column::make([
                    CustomDate::make('Действует с', 'entry_start')
                        ->onApply(function ($item, $value) {
                            $item->entry_start = ! empty($value) ? Carbon::parse($value)->startOfDay() : null;
                            return $item;
                        }),
                    ])->columnSpan(6),
                Column::make([
                    CustomDate::make('Действует до', 'entry_end')
                        ->afterField('entry_start', 'Конец действия не может быть раньше его начала')
                        ->onApply(function ($item, $value) {
                            $item->entry_end = ! empty($value) ? Carbon::parse($value)->endOfDay() : null;
                            return $item;
                        }),
                ])->columnSpan(6),
            ]),
            CustomTextarea::make('Комментарий', 'comment')
                ->nullable(),
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
