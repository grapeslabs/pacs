<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\Models\Organization;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\PhotoField;
use App\Models\Tag;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Storage;
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
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Contracts\UI\ActionButtonContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Eloquent\Builder;

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

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend($this->getKeyButton());
    }

    protected function modifyDetailButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->canSee(fn() => false);
    }

    public function indexQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::indexQuery()->with('tags');
    }

    public function trAttributes(): ?Closure
    {
        return function (mixed $item, int $index): array {
            if ($item === null) {
                return [];
            }
            $model = $item instanceof ModelDataWrapper ? $item->getOriginal() : $item;
            if (!isset($model->frozen_start)) {
                return [];
            }

            $now = Carbon::now();
            $isFrozen = $model->frozen_start !== null
                && $now->greaterThanOrEqualTo($model->frozen_start)
                && ($model->frozen_end === null || $now->lessThan($model->frozen_end));

            return $isFrozen ? ['class' => 'frozen'] : [];
        };
    }
    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('ФИО', 'last_name', fn($item)=>($item->last_name??'') . " " . ($item->first_name??'') . " " . ($item->middle_name??''))
                ->sortable(),
            Date::make('Дата рождения', 'birth_date')
                ->withTime(false)
                ->format('d.m.Y')
                ->sortable(),
            Text::make('Номер удостоверения', 'certificate_number')->sortable(),
            BelongsToMany::make('Теги', 'tags', 'name', resource: TagResource::class)
                ->inLine(' ',  fn($model, $value) => Badge::make((string) $value, 'primary'))
                ->sortable(function (
                    Builder $query,
                    string $column,
                    string $direction,
                ) {
                    return $query->orderBy(
                        DB::raw("(SELECT name FROM tags WHERE tags.id IN (SELECT tag_id FROM person_tag WHERE person_tag.person_id = person.id) ORDER BY name {$direction} LIMIT 1)"),
                        $direction,
                    );
                }),
            Image::make('Фото', 'photo')
                ->multiple()
                ->setLabel('Фото'),
            Select::make('Организация', 'organization_id')
                ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                ->sortable(),
            Text::make('Комментарий', 'comment')->sortable(),
            Date::make('Заморозить с', 'frozen_start'),
            Date::make('Заморозить до', 'frozen_end'),
        ];
    }

    public function formFields(): iterable
    {
        $this->getItem()?->load('tags');

        return [
            ID::make(),
            CustomText::make('Фамилия', 'last_name')
                ->min(2,'Минимум 2 символа')
                ->max(50, 'Максимум 50 символов')
                ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                ->nameFormat('Фамилия должна содержать буквы')
                ->placeholder('Иванов')
                ->required(),
            CustomText::make('Имя', 'first_name')
                ->min(2,'Минимум 2 символа')
                ->max(50, 'Максимум 50 символов')
                ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                ->nameFormat()
                ->required()
                ->placeholder('Иван'),
            CustomText::make('Отчество', 'middle_name')
                ->min(2,'Минимум 2 символа')
                ->max(50, 'Максимум 50 символов')
                ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                ->nameFormat('Отчество должно содержать буквы')
                ->placeholder('Иванович'),
            CustomDate::make('Дата рождения', 'birth_date')
                ->before(Carbon::now(), 'Дата рождения не может быть будущим')
                ->after(Carbon::now()->subYears(120), 'Дата рождения не может быть более 120 лет назад')
                ->format('d.m.Y')
                ->sortable(),
            CustomText::make('Номер удостоверения', 'certificate_number')
                ->unique('person', 'certificate_number', 'Номер удостоверения должен быть уникальным'),
            SelectField::make('Теги', 'tags')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
                ->creatable(true, route('tags.store')),
            PhotoField::make('Фото', 'photo')
                ->multiple()
                ->disk('public')
                ->dir('person/photos')
                ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),
            Select::make('Организация', 'organization_id')
                ->placeholder('Выберите организацию')
                ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                ->searchable()
                ->nullable(),
            Textarea::make('Комментарий', 'comment'),
            Date::make('Заморозить с', 'frozen_start')
                ->withTime()
                ->nullable(),
            Date::make('Заморозить до', 'frozen_end')
                ->withTime()
                ->nullable()
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

            Select::make('Организация', 'organization_id')
                ->placeholder('Фильтрация по организации')
                ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                ->searchable()
                ->nullable(),

            SelectField::make('Теги', 'tags')
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
            ->icon('key')
            ->class('js-key-button')
            ->customAttributes([
                '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Идентификация персоны' })",
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
            ->submit('Сохранить', ['class' => 'btn-primary']);
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
