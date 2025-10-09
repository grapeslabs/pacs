<?php

namespace App\MoonShine\Resources;

use App\Models\Key;
use App\Models\Person;
use App\Models\Organization;
use App\MoonShine\Fields\PhotoField;
use App\Models\Tag;
use App\MoonShine\Fields\Select2Field;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Carbon\Carbon;
use Closure;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\Services\VideoAnalyticService;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Contracts\UI\ActionButtonContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use GrapesLabs\PinvideoSkud\Models\SkudController;

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
        return parent::indexButtons()->prepend(
            ActionButton::make(
                '',
                fn($item) => toPage('form-page', app(KeyResource::class), ['person_id' => $item->id])
            )->icon('key')->class('btn-key')
        );
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
            Text::make('Фамилия', 'last_name')
                ->placeholder('Иванов')
                ->required(),
            Text::make('Имя', 'first_name')
                ->required()
                ->placeholder('Иван'),
            Text::make('Отчество', 'middle_name')
                ->placeholder('Иванович'),
            Date::make('Дата рождения', 'birth_date')
                ->placeholder('00.00.0000')
                ->format('d.m.Y')
                ->sortable(),
            Text::make('Номер удостоверения', 'certificate_number')->placeholder('XXXXXXXXXXXXX'),
            SelectField::make('Теги', 'tags')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
                ->creatable(true, route('tags.store')),
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
}
