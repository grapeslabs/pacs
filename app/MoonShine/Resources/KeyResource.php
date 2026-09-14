<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\UI\Components\ActionButton;

class KeyResource extends BaseModelResource
{
    protected string $model = Key::class;
    protected string $title = 'Ключи';
    public function indexSubtitle(): ?string
    {
        return $this->countSubtitle('ключ доступа', 'ключа доступа', 'ключей доступа');
    }
    protected ?string $editModalTitle = 'Редактирование ключа';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить ключ';

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
        $item->loadMissing('person.organization');
        $icCheck = $this->iconFile('check');
        $icKey = $this->iconFile('btn-addkey');
        $icGear = $this->iconFile('gear');
        $icPerson = $this->iconFile('person');

        $type = $item->type ?: '—';
        $active = (bool) $item->is_active;

        $sections = [
            [
                'icon' => $icGear,
                'title' => 'Параметры',
                'fields' => [
                    ['label' => 'Ключ', 'value' => $item->key],
                    ['label' => 'Тип ключа', 'value' => $type],
                    ['label' => 'Выдан', 'value' => $item->issued_at?->format('d.m.Y')],
                    ['label' => 'Действует до', 'value' => $item->expires_at?->format('d.m.Y') ?: 'Бессрочно'],
                ],
            ],
        ];

        if ($item->person) {
            $person = $item->person;
            $pSub = collect([
                $person->organization?->short_name,
                $person->certificate_number ? $person->certificate_number : null,
            ])->filter()->join(' · ');

            $sections[] = [
                'icon' => $icPerson,
                'title' => 'Персона',
                'items' => [[
                    'title' => trim($person->last_name . ' ' . $person->first_name . ' ' . ($person->middle_name ?? '')),
                    'subtitle' => $pSub,
                    'actions' => [$this->detailChevronTo(app(PersonResource::class), $person->getKey())],
                ]],
            ];
        }

        $icLockClosed = $this->iconFile('lock');
        $icLockOpen = $this->iconFile('open');

        $blockUrl = $this->getAsyncMethodUrl('toggleBlock', page: $this->getIndexPage(), params: ['item_id' => $item->getKey()]);
        $blockHtml = (string) ActionButton::make($active ? 'Заблокировать' : 'Разблокировать', $blockUrl)
            ->icon($active ? $icLockClosed : $icLockOpen, true)
            ->async()
            ->customAttributes(['class' => 'ds-btn ' . ($active ? 'ds-btn--danger' : 'ds-btn--secondary')]);

        return [
            'title' => 'Карточка ключа',
            'subtitle' => 'Ключ доступа · ' . $type,
            'hero' => [
                'title' => $item->key,
                'subtitle' => 'Ключ доступа · ' . $type,
                'icon' => $icKey,
                'badges' => [
                    $active
                        ? ['text' => 'Активен', 'icon' => $icCheck]
                        : ['text' => 'Заблокирован', 'tone' => 'danger'],
                ],
            ],
            'sections' => $sections,
            'footer' => [
                ['html' => $blockHtml],
                ['spacer' => true],
                ['html' => $this->detailEditButtonHtml($item)],
            ],
        ];
    }

    public function deleteConfirmation(Model $item): array
    {
        return [
            'title' => 'Удалить ключ?',
            'text'  => 'Ключ «' . ($item->key ?: '—') . '» и вся история по нему будут удалены безвозвратно. Если ключ утерян — лучше заблокировать его, сохранив историю.',
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
            AvatarField::make('Персона', 'person')
                ->text(fn($item) => $item->person?->getFullName() ?: '—')
                ->subtext(fn($item) => $item->person?->certificate_number ? $item->person->certificate_number : null)
                ->photo(fn($item) => $item->person?->first_photo)
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $query->orderBy(
                        DB::raw('(SELECT last_name FROM person WHERE person.id = keys.person_id)'),
                        $direction,
                    );
                }),
            ColoredSelectField::make('Статус', 'is_active', fn($item) => $item->is_active ? 'active' : 'blocked')
                ->options([
                    'active' => ['label' => 'Активен', 'color' => 'green'],
                    'blocked' => ['label' => 'Заблокирован', 'color' => 'red'],
                ]),
            Date::make('Выдан', 'issued_at')
                ->format('d.m.Y')
                ->sortable(),
            Date::make('Действует до', 'expires_at')
                ->format('d.m.Y')
                ->sortable(),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Ключ', 'key'),
            Text::make('Тип ключа', 'type'),
            Text::make('Персона', 'person_id')
                ->changeFill(fn($item) => $item->person
                    ? trim(($item->person->last_name ?? '') . ' ' . ($item->person->first_name ?? '') . ' ' . ($item->person->middle_name ?? ''))
                    : '—'),
            Text::make('Статус', 'is_active')
                ->changeFill(fn($item) => $item->is_active ? 'Активен' : 'Заблокирован'),
            Date::make('Выдан', 'issued_at')
                ->format('d.m.Y'),
            Date::make('Действует до', 'expires_at')
                ->format('d.m.Y'),
        ];
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend($this->blockButton());
    }

    protected function blockButton(): ActionButtonContract
    {
        return ActionButton::make('', fn($item) => $this->getAsyncMethodUrl('toggleBlock', params: ['item_id' => $item->getKey()]))
            ->icon($this->iconFile('lock'), true)
            ->class(['js-block-button', 'ds-rowbtn'])
            ->customAttributes([
                'title' => 'Заблокировать / Разблокировать',
                '@click.stop' => '',
            ])
            ->async();
    }

    public function toggleBlock(MoonShineRequest $request): MoonShineJsonResponse
    {
        $key = Key::query()->find($request->get('item_id'));

        if (! $key) {
            return MoonShineJsonResponse::make()->toast('Ключ не найден', ToastType::ERROR);
        }

        $key->is_active = ! $key->is_active;
        $key->save();

        return MoonShineJsonResponse::make()
            ->toast($key->is_active ? 'Ключ разблокирован' : 'Ключ заблокирован', ToastType::SUCCESS)
            ->events([$this->getListEventName($this->getListComponentName())]);
    }

    public function formFields(): iterable
    {
        return [
            CustomText::make('Ключ', 'key')
                ->leadingIcon(file_get_contents(public_path('icons/btn-addkey.svg')))
                ->max(255, 'Ключ не может содержать более 255 символов')
                ->unique('keys', 'key', 'Ключ должен быть уникальным')
                ->required(),
            SelectField::make('Тип ключа', 'type')
                ->options(Key::TYPES)
                ->required()
                ->default('Mifare'),
            SelectField::make('Персона', 'person_id')
                ->icon('magnifier')
                ->optionsIcon('person')
                ->options(Person::query()->get()->map(fn($p) => [
                    'id'      => $p->id,
                    'name'    => trim(($p->last_name ?? '') . ' ' . ($p->first_name ?? '') . ' ' . ($p->middle_name ?? '')),
                    'subtext' => $p->certificate_number ? $p->certificate_number : null,
                ])->toArray())
                ->required()
                ->default(request()->person_id??null),
            Date::make('Выдан', 'issued_at'),
            Date::make('Действует до', 'expires_at'),
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

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->with('person');
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
                ])
                ->nullable(),
            SelectField::make('Персона', 'person_id')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->placeholder('Фильтрация по имени')
                ->nullable(),
        ];
    }

}
