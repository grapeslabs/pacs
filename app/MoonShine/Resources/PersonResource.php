<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\Models\Organization;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\PhotoField;
use App\Models\Tag;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Pages\CustomIndexPage;
use App\MoonShine\Resources\OrganizationResource;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\Services\VideoAnalyticService;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Select;
use App\MoonShine\Fields\CustomTextarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PersonResource extends BaseModelResource
{
    private VideoAnalyticService $vas;
    public function __construct(CoreContract $core, VideoAnalyticService $vas)
    {
        parent::__construct($core);
        $this->vas = $vas;
    }

    protected string $model = Person::class;
    protected string $title = 'Персоны';
    protected string $ex_type = 'pingate';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить персону';

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
        $item->loadMissing(['organization', 'tags', 'keys']);

        $org = $item->organization?->short_name;
        $cert = $item->certificate_number;

        $heroSub = collect([$org, $cert ? '№ ' . $cert : null])->filter()->join(' · ');

        $photo = $item->first_photo;
        $avatar = $photo ? Storage::disk('public')->url($photo) : null;
        $initials = mb_strtoupper(mb_substr($item->last_name ?? '', 0, 1) . mb_substr($item->first_name ?? '', 0, 1));

        $now = now();
        $frozen = $item->frozen_start
            && $item->frozen_start <= $now
            && (is_null($item->frozen_end) || $item->frozen_end >= $now);

        $keys = $item->keys;
        $keysCount = $keys->count();

        $keyRes = app(KeyResource::class);
        $keysUrl = $this->filteredIndexUrl($keyRes, ['person_id' => $item->getKey()]);

        $icPerson = '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
        $icTag = '<svg viewBox="0 0 24 24" fill="none"><path d="M3 11.5 11.5 3H20v8.5L11.5 20z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="15.5" cy="8.5" r="1.4" fill="currentColor"/></svg>';
        $icKey = '<svg viewBox="0 0 24 24" fill="none"><circle cx="8" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="m11 11 8 8M16 16l2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
        $icCheck = '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6z" stroke="currentColor" stroke-width="1.6"/><path d="m9 11.5 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $icSnow = '<svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M4.5 6l15 12M19.5 6l-15 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M12 6.5 9.5 5m2.5 1.5L14.5 5M12 17.5 9.5 19m2.5-1.5L14.5 19M5.5 8.8 5 6m.5 2.8L3 9.4M18.5 8.8 19 6m-.5 2.8L21 9.4M5.5 15.2 5 18m.5-2.8L3 14.6M18.5 15.2 19 18m-.5-2.8L21 14.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $badges = [
            $frozen
                ? ['text' => 'Доступ заморожен', 'tone' => 'danger', 'icon' => $icSnow]
                : ['text' => 'Доступ активен', 'icon' => $icCheck],
        ];
        if ($keysCount > 0) {
            $badges[] = ['text' => $keysCount . ' ' . $this->pluralKeys($keysCount), 'icon' => $icKey, 'url' => $keysUrl];
        }

        $sections = [
            [
                'icon' => $icPerson,
                'title' => 'Личные данные',
                'fields' => [
                    ['label' => 'Фамилия', 'value' => $item->last_name],
                    ['label' => 'Имя', 'value' => $item->first_name],
                    ['label' => 'Отчество', 'value' => $item->middle_name],
                    ['label' => 'Дата рождения', 'value' => $item->birth_date?->format('d.m.Y')],
                    ['label' => 'Номер удостоверения', 'value' => $cert],
                    ['label' => 'Организация', 'value' => $org],
                ],
            ],
        ];

        if ($item->tags->isNotEmpty()) {
            $sections[] = [
                'icon' => $icTag,
                'title' => 'Теги',
                'tags' => $item->tags->pluck('name')->all(),
            ];
        }

        if ($keysCount > 0) {
            $sections[] = [
                'icon' => $icKey,
                'title' => 'Ключи доступа · ' . $keysCount,
                'items' => $keys->map(fn($k) => [
                    'title' => $k->key,
                    'subtitle' => $k->type,
                    'badge' => $k->is_active
                        ? ['text' => 'Активен', 'tone' => 'ok']
                        : ['text' => 'Заблокирован', 'tone' => 'danger'],
                    'actions' => [
                        $keyRes->detailEditIconButton($k, 'Редактирование ключа'),
                        $this->detailChevronTo($keyRes, $k->getKey()),
                    ],
                ])->all(),
            ];
        }

        if (! blank($item->comment)) {
            $sections[] = [
                'icon' => $this->commentIcon(),
                'title' => 'Комментарий',
                'text' => $item->comment,
            ];
        }

        $keyUrl = $this->getAsyncMethodUrl('getKeyForm', page: $this->getIndexPage(), params: [
            'item_id' => $item->getKey(),
            '_component_name' => $this->getListComponentName(),
            '_async_form' => true,
        ]);
        $issueKeyHtml = (string) ActionButton::make('Выдать ключ', $keyUrl)
            ->icon(file_get_contents(public_path('icons/btn-addkey.svg')), true)
            ->async(selector: "#{$this->safeModalName}_content")
            ->customAttributes([
                'class' => 'ds-btn ds-btn--secondary',
                '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Выдача ключа' }); \$dispatch('modal-toggled', { id: 'detail-sidebar' })",
            ]);

        $editHtml = $this->detailEditButtonHtml($item);

        $icWarn = '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3 2.5 20h19z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M12 10v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="17" r="1" fill="currentColor"/></svg>';

        return [
            'title' => 'Карточка персоны',
            'subtitle' => 'ID ' . $item->getKey(),
            'banner' => $frozen ? [
                'tone' => 'warn',
                'icon' => $icWarn,
                'text' => 'Доступ персоны временно заморожен — карты и распознавание лица не пропускают через СКУД.',
            ] : null,
            'hero' => [
                'title' => $item->getFullName(),
                'subtitle' => $heroSub,
                'avatar' => $avatar,
                'initials' => $avatar ? null : $initials,
                'badges' => $badges,
            ],
            'sections' => $sections,
            'footer' => [
                ['html' => $issueKeyHtml],
                ['spacer' => true],
                ['html' => $editHtml],
            ],
        ];
    }

    private function pluralKeys(int $n): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 'ключ';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ! ($mod100 >= 12 && $mod100 <= 14)) {
            return 'ключа';
        }

        return 'ключей';
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend($this->getKeyButton());
    }

    public function indexQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::indexQuery()->with('tags');
    }

    public function trAttributes(): ?Closure
    {
        $parentClosure = parent::trAttributes() ?? fn() => [];
        return function (mixed $item, int $index) use ($parentClosure): array {
            $attrs = $parentClosure($item, $index);

            if ($item === null) {
                return $attrs;
            }
            $model = $item instanceof ModelDataWrapper ? $item->getOriginal() : $item;
            if (!isset($model->frozen_start)) {
                return $attrs;
            }

            $now = Carbon::now();
            $isFrozen = $model->frozen_start !== null
                && $now->greaterThanOrEqualTo($model->frozen_start)
                && ($model->frozen_end === null || $now->lessThan($model->frozen_end));

            if ($isFrozen) {
                $attrs['class'] = trim(($attrs['class'] ?? '') . ' frozen');
            }

            return $attrs;
        };
    }
    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            AvatarField::make('ФИО', 'last_name')
                ->text(fn($item) => trim(($item->last_name ?? '') . ' ' . ($item->first_name ?? '') . ' ' . ($item->middle_name ?? '')))
                ->subtext(fn($item) => '№' . $item->certificate_number)
                ->photo(fn($item) => is_array($item->photo) ? ($item->photo[0] ?? null) : $item->photo)
                ->indicator(fn($item) => $item && $item->isFrozen()
                    ? '<svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M4.5 6l15 12M19.5 6l-15 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M12 6.5 9.5 5m2.5 1.5L14.5 5M12 17.5 9.5 19m2.5-1.5L14.5 19M5.5 8.8 5 6m.5 2.8L3 9.4M18.5 8.8 19 6m-.5 2.8L21 9.4M5.5 15.2 5 18m.5-2.8L3 14.6M18.5 15.2 19 18m-.5-2.8L21 14.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    : null)
                ->sortable(),
            Date::make('Дата рождения', 'birth_date')
                ->withTime(false)
                ->format('d.m.Y')
                ->sortable(),
            BelongsTo::make('Организация', 'organization', fn($item) => $item->short_name, resource: OrganizationResource::class)
                ->sortable(),
            \App\MoonShine\Fields\CountField::make('Ключи', 'id', fn($item) => $item->keys()->count()),
            \App\MoonShine\Fields\TagsField::make('Теги', 'tags', fn($item) => $item->tags->pluck('name')->all()),
            Image::make('Фотобанк', 'photo')
                ->multiple()
                ->setLabel('Фотобанк'),
            ColoredSelectField::make('Статус', 'frozen_start', fn($item) => $item && $item->isFrozen() ? 'frozen' : 'active')
                ->options([
                    'active' => ['label' => 'Активен', 'color' => 'green'],
                    'frozen' => ['label' => 'Заморожен', 'color' => 'orange'],
                ]),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    public function formFields(): iterable
    {
        $this->getItem()?->load('tags');

        return [
            Grid::make([
                Column::make([
                    CustomText::make('Фамилия', 'last_name')
                        ->min(2,'Минимум 2 символа')
                        ->max(50, 'Максимум 50 символов')
                        ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                        ->nameFormat('Фамилия должна содержать буквы')
                        ->placeholder('Иванов')
                        ->required(),
                ])->columnSpan(6),
                Column::make([
                    CustomText::make('Имя', 'first_name')
                        ->min(2,'Минимум 2 символа')
                        ->max(50, 'Максимум 50 символов')
                        ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                        ->nameFormat()
                        ->required()
                        ->placeholder('Иван'),
                ])->columnSpan(6),
            ]),
            Grid::make([
                Column::make([
                    CustomText::make('Отчество', 'middle_name')
                        ->min(2,'Минимум 2 символа')
                        ->max(50, 'Максимум 50 символов')
                        ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                        ->nameFormat('Отчество должно содержать буквы')
                        ->placeholder('Иванович'),
                ])->columnSpan(6),
                Column::make([
                    CustomDate::make('Дата рождения', 'birth_date')
                        ->before(Carbon::now(), 'Дата рождения не может быть будущим')
                        ->after(Carbon::now()->subYears(120), 'Дата рождения не может быть более 120 лет назад')
                        ->format('d.m.Y')
                        ->sortable(),
                ])->columnSpan(6),
            ]),
            Grid::make([
                Column::make([
                    CustomText::make('Номер удостоверения', 'certificate_number')
                        ->unique('person', 'certificate_number', 'Номер удостоверения должен быть уникальным'),
                ])->columnSpan(6),
                Column::make([
                    SelectField::make('Организация', 'organization_id')
                        ->placeholder('Выберите организацию')
                        ->icon('search')
                        ->optionsIcon('organization')
                        ->options(Organization::query()->get()->map(fn($o) => [
                            'id'      => $o->id,
                            'name'    => $o->short_name,
                            'subtext' => $o->inn ? 'ИНН ' . $o->inn : null,
                        ])->toArray())
                        ->nullable(),
                ])->columnSpan(6),
            ]),

            PhotoField::make('Фото', 'photo')
                ->multiple()
                ->disk('public')
                ->dir('person/photos')
                ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),
            SelectField::make('Теги', 'tags')
                ->icon('tag')
                ->optionsIcon('tag')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
                ->creatable(true, route('tags.store')),
            CustomTextarea::make('Комментарий', 'comment'),
            \App\MoonShine\Fields\FreezeField::make('Заморозка доступа', 'frozen_start'),
        ];
    }

    public function search(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'middle_name',
            'certificate_number',
            'organization.short_name',
            'birth_date',
            'comment',
        ];
    }

    public function filters(): array
    {
        return [
            Text::make('Фамилия', 'last_name')
                ->placeholder('Фильтрация по фамилии'),

            Text::make('Имя', 'first_name')
                ->placeholder('Фильтрация по имени'),

            Text::make('Отчество', 'middle_name')
                ->placeholder('Фильтрация по отчеству'),

            Text::make('Номер удостоверения', 'certificate_number')
                ->placeholder('Фильтрация по номеру удостоверения'),

            Date::make('Дата рождения', 'birth_date')
                ->placeholder('Фильтрация по дате рождения'),

            SelectField::make('Организация', 'organization_id')
                ->placeholder('Фильтрация по организации')
                ->icon('search')
                ->optionsIcon('organization')
                ->options(Organization::query()->get()->map(fn($o) => [
                    'id'      => $o->id,
                    'name'    => $o->short_name,
                    'subtext' => $o->inn ? 'ИНН ' . $o->inn : null,
                ])->toArray())
                ->nullable(),

            SelectField::make('Теги', 'tags')
                ->icon('tag')
                ->optionsIcon('tag')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
        ];
    }

    public function rules($item): array
    {
        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'certificate_number' => 'nullable|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'photo' => 'nullable|array',
            'photo.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function validationMessages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Дата рождения не может быть в будущем',
            'last_name.required' => 'Поле "Фамилия" обязательно для заполнения',
            'photo.*.image' => 'Недопустимый формат файла',
            'photo.*.mimes' => 'Допустимые форматы: JPG, JPEG, PNG, WEBP',
            'photo.*.max' => 'Размер изображения не должен превышать 5 МБ',
        ];
    }

    protected function getKeyButton(): ActionButton
    {
        return ActionButton::make(
            '',
            fn($item) => $this->getAsyncMethodUrl(
                method: 'getKeyForm',
                params: [
                    'item_id' => $item->getKey(),
                    '_component_name' => $this->getListComponentName(),
                    '_async_form' => true,
                ]
            )
        )
            ->icon(file_get_contents(public_path('icons/btn-addkey.svg')), true)
            ->class('js-key-button')
            ->customAttributes([
                '@click.prevent.stop' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Выдача ключа' })",
            ])
            ->async(selector: "#{$this->safeModalName}_content");
    }

    public function getKeyForm(MoonShineRequest $request): string
    {
        $itemId = $request->get('item_id');
        return FormBuilder::make()
            ->action($this->getAsyncMethodUrl('saveKey', null, ['item_id' => $itemId]))
            ->async()
            ->fields([
                    Hidden::make('person_id')->default($itemId),
                    CustomText::make('Ключ', 'key')
                        ->max(255)
                        ->unique('keys', 'key', 'Ключ должен быть уникальным')
                        ->required(),
                    Select::make('Тип ключа', 'type')
                        ->options([
                            'Mifare' => 'Mifare',
                        ])
                        ->required()
                        ->default('Mifare'),
                ]
            )
            ->submit('Сохранить', ['class' => 'btn-primary', 'style' => 'margin-left: 1.5rem']);
    }

    public function saveKey(MoonShineRequest $request)
    {
        $data = $request->all();
        $key = Key::create([
            'person_id' => $data['person_id'] ?? null,
            'type' => $data['type'] ?? null,
            'key' => $data['key'] ?? null,
        ]);
        return MoonShineJsonResponse::make()
            ->toast('Ключ успешно добавлен!', ToastType::SUCCESS)
            ->redirect(app(KeyResource::class)->getUrl());
    }

}
