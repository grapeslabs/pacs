<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Database\Eloquent\Model;
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

        $icKey = '<svg viewBox="0 0 24 24" fill="none"><circle cx="8" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="m11 11 8 8M16 16l2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
        $icGear = '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.5 5.5l2 2M16.5 16.5l2 2M18.5 5.5l-2 2M7.5 16.5l-2 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        $icPerson = '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';

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
                $person->certificate_number ? '№ ' . $person->certificate_number : null,
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

        $icLockClosed = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="15.5" r="1.4" fill="currentColor"/></svg>';
        $icLockOpen = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10.5V8a4 4 0 0 1 7.5-1.9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="15.5" r="1.4" fill="currentColor"/></svg>';

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
                        ? ['text' => 'Активен']
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

    public function indexFields(): iterable
    {
        return [
            Text::make('Ключ', 'key')
                ->sortable(),
            Select::make('Тип ключа', 'type')
                ->options(Key::TYPES)
                ->sortable(),
            BelongsTo::make('Персона', 'person', fn($item) => trim(($item->last_name ?? '') . ' ' . ($item->first_name ?? '')), resource: PersonResource::class)
                ->sortable(),
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
            ->icon('lock-closed')
            ->class('js-block-button')
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
                ->max(255, 'Ключ не может содержать более 255 символов')
                ->unique('keys', 'key', 'Ключ должен быть уникальным')
                ->required(),
            SelectField::make('Тип ключа', 'type')
                ->options(Key::TYPES)
                ->required()
                ->default('Mifare'),
            SelectField::make('Персона', 'person_id')
                ->icon('search')
                ->optionsIcon('icon-person')
                ->options(Person::query()->get()->map(fn($p) => [
                    'id'      => $p->id,
                    'name'    => trim(($p->last_name ?? '') . ' ' . ($p->first_name ?? '') . ' ' . ($p->middle_name ?? '')),
                    'subtext' => $p->certificate_number ? '№ ' . $p->certificate_number : null,
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
