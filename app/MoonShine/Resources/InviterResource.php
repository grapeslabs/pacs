<?php

namespace App\MoonShine\Resources;

use App\Models\Inviter;
use App\Models\Person;
use App\Models\Organization;
use App\Models\Tag;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Validation\Rule;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Select;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;

class InviterResource extends BaseModelResource
{
    protected string $model = Inviter::class;
    protected string $title = 'Приглашающие';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    /**
     * Переопределяем основной запрос для добавления JOIN и вычисляемого поля для тегов
     */
    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->selectRaw('inviters.*,
            COALESCE((
                SELECT MIN(t.name)
                FROM person_tag pt
                JOIN tags t ON pt.tag_id = t.id
                WHERE pt.person_id = person.id
            ), \'\') as first_tag_name')
            ->leftJoin('person', 'inviters.person_id', '=', 'person.id')
            ->leftJoin('organizations', 'person.organization_id', '=', 'organizations.id');
    }

    /**
     * Запрос для индексной страницы
     */
    public function indexQuery(): Builder
    {
        $query = $this->modifyQueryBuilder(
            parent::indexQuery()
        )->with(['person', 'person.organization', 'person.tags']);

        $request = request();

        // Обработка сортировки
        if ($request->has('sort')) {
            $sortField = $request->get('sort');
            $direction = $request->get('direction', 'asc');

            // Блокируем сортировку по photo
            if (str_contains($sortField, 'photo')) {
                return $query->orderBy('inviters.id', 'asc');
            }

            // Специальная обработка для сортировки по тегам
            if ($sortField === 'first_tag_name') {
                return $query->orderBy('first_tag_name', $direction);
            }
        }

        // Если нет сортировки или она пустая
        if (!$request->has('sort') || empty(trim($request->get('sort')))) {
            return $query->orderBy('inviters.id', 'asc');
        }

        return $query;
    }

    public function indexFields(): iterable
    {
        return [
            // Фамилия
            Text::make('Фамилия', 'person.last_name')
                ->sortable('person.last_name'),

            // Имя
            Text::make('Имя', 'person.first_name')
                ->sortable('person.first_name'),

            // Отчество
            Text::make('Отчество', 'person.middle_name')
                ->sortable('person.middle_name'),

            // Организация
            Text::make('Организация', 'person.organization.short_name')
                ->sortable('organizations.short_name'),

            // Дата рождения
            Date::make('Дата рождения', 'person.birth_date')
                ->withTime(false)
                ->format('d.m.Y')
                ->sortable('person.birth_date'),

            // Номер удостоверения
            Text::make('Номер удостоверения', 'person.certificate_number')
                ->sortable('person.certificate_number'),

            // Теги - исправленное отображение
            Text::make('Теги')
                ->changeFill(function($item) {
                    if ($item instanceof Inviter && $item->person) {
                        if ($item->person->relationLoaded('tags') && $item->person->tags->isNotEmpty()) {
                            return $item->person->tags->pluck('name')->implode(', ');
                        }

                        $item->person->load('tags');
                        if ($item->person->tags->isNotEmpty()) {
                            return $item->person->tags->pluck('name')->implode(', ');
                        }
                    }
                    return '—';
                })
                ->sortable('first_tag_name'), // Сортируем по вычисляемому полю

            // Фото - не сортируем
            Image::make('Фото', 'person.photo')
                ->multiple()
                ->setLabel('Фото'),

            // Комментарий
            Text::make('Комментарий', 'person.comment')
                ->sortable('person.comment'),

            // Пользователь - сортировка по полю основной таблицы
            Text::make('Пользователь', 'user_name')
                ->sortable('inviters.user_name'),
        ];
    }

    /**
     * Поля для формы
     */
    public function formFields(): iterable
    {
        $existingPersonIds = Inviter::pluck('person_id')->toArray();

        return [
            ID::make(),
            Text::make('Пользователь', 'user_name')
                ->default(auth()->user()->name ?? 'System')
                ->readonly()
                ->required(),
            Select::make('Персона', 'person_id')
                ->options(
                    Person::query()
                        ->with('organization')
                        ->when($existingPersonIds, function ($query) use ($existingPersonIds) {
                            return $query->whereNotIn('id', $existingPersonIds);
                        })
                        ->get()
                        ->mapWithKeys(function (Person $person) {
                            $organization = $person->organization ? $person->organization->short_name . ' - ' : '';
                            $phone = $person->phone ? ' (' . $person->phone . ')' : '';
                            return [
                                $person->id => "{$person->getFullName()} {$organization}{$phone}"
                            ];
                        })
                        ->toArray()
                )
                ->searchable()
                ->nullable()
                ->placeholder('Выберите персону')
                ->required(),
        ];
    }

    /**
     * Правила валидации
     */
    public function rules($item): array
    {
        return [
            'user_name' => 'required|string|max:255',
            'person_id' => [
                'required',
                'exists:person,id',
                Rule::unique('inviters', 'person_id')->ignore($item->id ?? null),
            ],
        ];
    }

    /**
     * Сообщения валидации
     */
    public function validationMessages(): array
    {
        return [
            'user_name.required' => 'Поле "Пользователь" обязательно для заполнения',
            'person_id.required' => 'Поле "Персона" обязательно для заполнения',
            'person_id.exists' => 'Выбранная персона не существует',
            'person_id.unique' => 'Эта персона уже добавлена в список приглашающих',
        ];
    }

    /**
     * Фильтры
     */
    public function filters(): array
    {
        return [
            Text::make('Пользователь', 'user_name')
                ->placeholder('Фильтрация по пользователю'),
            Select::make('Персона', 'person_id')
                ->options(
                    Person::query()
                        ->get()
                        ->mapWithKeys(fn (Person $person) => [
                            $person->id => $person->getFullName()
                        ])
                        ->toArray()
                )
                ->searchable()
                ->nullable()
                ->placeholder('Фильтрация по персоне'),
            Select::make('Организация', 'person.organization_id')
                ->options(
                    Organization::query()
                        ->get()
                        ->pluck('short_name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->nullable()
                ->placeholder('Фильтрация по организации')
                ->onApply(function (Builder $query, $value) {
                    return $query->whereHas('person.organization', function ($q) use ($value) {
                        $q->where('id', $value);
                    });
                }),
            Select::make('Теги', 'person.tags')
                ->multiple()
                ->options(
                    Tag::query()
                        ->get()
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->nullable()
                ->placeholder('Фильтрация по тегам')
                ->onApply(function (Builder $query, $value) {
                    return $query->whereHas('person.tags', function ($q) use ($value) {
                        if (is_array($value)) {
                            $q->whereIn('tags.id', $value);
                        } else {
                            $q->where('tags.id', $value);
                        }
                    });
                }),
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::VIEW);
    }

    protected function beforeSave(Inviter $item): void
    {
        if (empty($item->user_name)) {
            $item->user_name = auth()->user()->name ?? 'System';
        }
    }

    /**
     * Поиск по связанным полям
     */
    public function searchQuery(string $terms): void
    {
        $this->newQuery()->where(function ($query) use ($terms) {
            $query->where('user_name', 'ILIKE', "%{$terms}%")
                ->orWhereHas('person', function ($q) use ($terms) {
                    $q->where('first_name', 'ILIKE', "%{$terms}%")
                        ->orWhere('last_name', 'ILIKE', "%{$terms}%")
                        ->orWhere('middle_name', 'ILIKE', "%{$terms}%")
                        ->orWhere('certificate_number', 'ILIKE', "%{$terms}%")
                        ->orWhere('comment', 'ILIKE', "%{$terms}%")
                        ->orWhereHas('organization', function ($orgQ) use ($terms) {
                            $orgQ->where('short_name', 'ILIKE', "%{$terms}%");
                        })
                        ->orWhereHas('tags', function ($tagQ) use ($terms) {
                            $tagQ->where('name', 'ILIKE', "%{$terms}%");
                        });
                });
        });
    }

    /**
     * Кнопки действий
     */
    public function actions(): \MoonShine\Laravel\Actions\Actions
    {
        return \MoonShine\Laravel\Actions\Actions::make([
            // Кнопка редактирования
            \MoonShine\Laravel\Components\ActionButton::make('Редактировать')
                ->url(function($data) {
                    $inviterId = $this->extractInviterId($data);

                    if (!$inviterId) {
                        return '#';
                    }

                    return moonshineRouter()->to('resource.edit', [
                        'resourceItem' => $inviterId,
                        'resourceUri' => $this->uriKey()
                    ]);
                })
                ->showInLine()
                ->icon('heroicons.outline.pencil'),

            // Кнопка удаления
            \MoonShine\Laravel\Components\ActionButton::make('Удалить')
                ->method('DELETE')
                ->url(function($data) {
                    $inviterId = $this->extractInviterId($data);

                    if (!$inviterId) {
                        return '#';
                    }

                    return moonshineRouter()->to('resource.delete', [
                        'resourceItem' => $inviterId,
                        'resourceUri' => $this->uriKey()
                    ]);
                })
                ->withConfirm('Подтверждение удаления', 'Вы уверены, что хотите удалить эту запись?', 'Да')
                ->showInLine()
                ->icon('heroicons.outline.trash')
        ]);
    }

    /**
     * Извлекает inviter_id из данных строки таблицы
     */
    private function extractInviterId($data): ?int
    {
        // Если это модель Inviter
        if ($data instanceof Inviter) {
            return $data->id;
        }

        // Если это объект из запроса с JOIN
        if (is_object($data)) {
            // Ищем поле id из таблицы inviters
            if (isset($data->id)) {
                return (int) $data->id;
            }
        }

        // Если это массив
        if (is_array($data)) {
            return $data['id'] ?? null;
        }

        // Если это просто число
        if (is_numeric($data)) {
            return (int) $data;
        }

        return null;
    }

    /**
     * Переопределяем resolveItem для надежного поиска
     */
    public function resolveItem(mixed $key): ?Inviter
    {
        if (is_numeric($key)) {
            return $this->newQuery()->find($key);
        }

        return null;
    }

    /**
     * Используем стандартное событие для логирования удаления
     */
    protected function beforeDeleting(mixed $item): mixed
    {
        \Log::info('Удаление Inviter', [
            'id' => $item->id,
            'user' => auth()->user()->name ?? 'unknown',
            'person_id' => $item->person_id
        ]);

        return $item;
    }
}
