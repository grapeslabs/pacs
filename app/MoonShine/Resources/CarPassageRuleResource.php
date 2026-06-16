<?php

namespace App\MoonShine\Resources;

use App\Models\Car;
use App\Models\CarPassageRule;
use App\Models\CarTag;
use App\Models\Passage;
use App\Models\Person;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomTextarea;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\Enums\ToastType;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

class CarPassageRuleResource extends BaseModelResource
{
    protected string $model = CarPassageRule::class;
    protected string $title = 'Правила проезда';
    protected string $column = 'name';

    private const TYPE_OPTIONS = [
        CarPassageRule::TYPE_ALLOW => ['label' => 'Разрешить', 'color' => 'green'],
        CarPassageRule::TYPE_DENY  => ['label' => 'Запретить', 'color' => 'red'],
    ];

    private const DIRECTION_OPTIONS = [
        CarPassageRule::DIRECTION_ENTRY => ['label' => 'Въезд',            'color' => 'green'],
        CarPassageRule::DIRECTION_EXIT  => ['label' => 'Выезд',            'color' => 'blue'],
        CarPassageRule::DIRECTION_BOTH  => ['label' => 'Оба направления',  'color' => 'purple'],
    ];

    protected function pages(): array
    {
        return [CustomIndexPage::class, DetailPage::class, FormPage::class];
    }


    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make('Наименование', 'name')->sortable(),

            Text::make('Теги', 'carTags', fn($item) => $item->carTags->pluck('name')->implode(', ') ?: '—'),

            Text::make('Персоны', 'people', fn($item) => $item->people
                ->map(fn(Person $p) => $p->getFullName())->implode(', ') ?: '—'),

            Text::make('Номера автомобилей', 'cars', fn($item) => $item->cars->pluck('license_plate')->implode(', ') ?: '—'),

            Text::make('Проезды', 'passages', fn($item) => $item->passages->pluck('name')->implode(', ') ?: '—'),

            ColoredSelectField::make('Направление', 'direction')->options(self::DIRECTION_OPTIONS),

            ColoredSelectField::make('Тип правила', 'type')->options(self::TYPE_OPTIONS),

            Text::make('Комментарий', 'comment'),
        ];
    }


    public function formFields(): iterable
    {
        return [
            CustomText::make('Наименование', 'name')
                ->max(255, "Наименование не может содержать более 255 символов")
                ->required(),

            SelectField::make('Теги авто', 'carTags')
                ->multiple()
                ->options(CarTag::query()->pluck('name', 'id')->toArray()),

            SelectField::make('Персоны (ФИО)', 'people')
                ->multiple()
                ->options($this->personOptions()),

            SelectField::make('Номера автомобилей', 'cars')
                ->multiple()
                ->options(Car::query()->pluck('license_plate', 'id')->toArray()),

            SelectField::make('Проезды', 'passages')
                ->multiple()
                ->required()
                ->options(Passage::query()->pluck('name', 'id')->toArray()),

            SelectField::make('Направление', 'direction')
                ->required()
                ->default(CarPassageRule::DIRECTION_BOTH)
                ->options(CarPassageRule::DIRECTIONS),

            SelectField::make('Тип правила', 'type')
                ->required()
                ->default(CarPassageRule::TYPE_ALLOW)
                ->options(CarPassageRule::TYPES),

            Switcher::make('Активно', 'is_active')->default(true),

            CustomTextarea::make('Комментарий', 'comment')->nullable(),
        ];
    }


    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Наименование', 'name'),
            Text::make('Теги авто', 'carTags', fn($item) => $item->carTags->pluck('name')->implode(', ') ?: '—'),
            Text::make('Персоны', 'people', fn($item) => $item->people->map(fn(Person $p) => $p->getFullName())->implode(', ') ?: '—'),
            Text::make('Номера автомобилей', 'cars', fn($item) => $item->cars->pluck('license_plate')->implode(', ') ?: '—'),
            Text::make('Проезды', 'passages', fn($item) => $item->passages->pluck('name')->implode(', ') ?: '—'),
            ColoredSelectField::make('Направление', 'direction')->options(self::DIRECTION_OPTIONS),
            ColoredSelectField::make('Тип правила', 'type')->options(self::TYPE_OPTIONS),
            Switcher::make('Активно', 'is_active'),
            Text::make('Комментарий', 'comment'),
        ];
    }


    public function rules($item): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', 'in:' . CarPassageRule::TYPE_ALLOW . ',' . CarPassageRule::TYPE_DENY],
            'direction'  => ['required', 'in:' . implode(',', array_keys(CarPassageRule::DIRECTIONS))],
            'comment'    => ['nullable', 'string'],
            'carTags'    => ['nullable', 'array'],
            'carTags.*'  => ['exists:car_tags,id'],
            'people'     => ['nullable', 'array'],
            'people.*'   => ['exists:person,id'],
            'cars'       => ['nullable', 'array'],
            'cars.*'     => ['exists:cars,id'],
            'passages'   => ['required', 'array', 'min:1'],
            'passages.*' => ['exists:passages,id'],
        ];
    }

    public function modifySaveResponse(MoonShineJsonResponse $response): MoonShineJsonResponse
    {
        $item = $this->getItem();

        if (! $item instanceof Model) {
            return $response;
        }
        $item->load('passages');
        $missing = $this->missingControllers($item);

        if (!empty($missing)) {
            $response->toast(
                'Сохранено. Внимание: для направления «' . CarPassageRule::DIRECTIONS[$item->direction] . '» '
                . 'не настроены контроллеры в проездах: ' . implode('; ', $missing),
                ToastType::WARNING,
            );
        }

        return $response;
    }

    private function missingControllers(Model $item): array
    {
        $item->loadMissing('passages');
        $direction = $item->direction;
        $missing = [];

        foreach ($item->passages as $passage) {
            if (in_array($direction, [CarPassageRule::DIRECTION_ENTRY, CarPassageRule::DIRECTION_BOTH], true)
                && ! $passage->hasEntryActuatorDevice()) {
                $missing[] = "«{$passage->name}» - нет устройства въезда";
            }
            if (in_array($direction, [CarPassageRule::DIRECTION_EXIT, CarPassageRule::DIRECTION_BOTH], true)
                && ! $passage->hasExitActuatorDevice()) {
                $missing[] = "«{$passage->name}» - нет устройства выезда";
            }
        }

        return $missing;
    }

    public function trAttributes(): ?Closure
    {
        $parent = parent::trAttributes() ?? fn() => [];

        return function (mixed $item, int $index) use ($parent): array {
            $attributes = $parent($item, $index);

            if ($item === null) {
                return $attributes;
            }

            $model = $item instanceof ModelDataWrapper ? $item->getOriginal() : $item;

            if ($model instanceof Model) {
                $missing = $this->missingControllers($model);

                if (! empty($missing)) {
                    $attributes['class'] = trim(($attributes['class'] ?? '') . ' passage-misconfigured');
                }
            }

            return $attributes;
        };
    }

    public function search(): array
    {
        return [
            'name',
            'comment',
            'carTags.name',
            'people.last_name',
            'people.first_name',
            'cars.license_plate',
            'passages.name',
        ];
    }

    public function filters(): array
    {
        return [
            CustomText::make('Наименование', 'name'),

            SelectField::make('Теги авто', 'car_tags_filter')
                ->multiple()->nullable()
                ->options(CarTag::query()->pluck('name', 'id')->toArray())
                ->onApply($this->relationFilter('carTags', 'car_tags.id')),

            SelectField::make('Персоны', 'people_filter')
                ->multiple()->nullable()
                ->options($this->personOptions())
                ->onApply($this->relationFilter('people', 'person.id')),

            SelectField::make('Номера автомобилей', 'cars_filter')
                ->multiple()->nullable()
                ->options(Car::query()->pluck('license_plate', 'id')->toArray())
                ->onApply($this->relationFilter('cars', 'cars.id')),

            SelectField::make('Проезды', 'passages_filter')
                ->multiple()->nullable()
                ->options(Passage::query()->pluck('name', 'id')->toArray())
                ->onApply($this->relationFilter('passages', 'passages.id')),

            SelectField::make('Направление', 'direction')
                ->nullable()
                ->options(CarPassageRule::DIRECTIONS),

            SelectField::make('Тип правила', 'type')
                ->nullable()
                ->options(CarPassageRule::TYPES),

            CustomText::make('Комментарий', 'comment'),
        ];
    }

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->with(['carTags', 'people', 'cars', 'passages']);
    }

    private function relationFilter(string $relation, string $qualifiedKey): \Closure
    {
        return function (Builder $query, $value) use ($relation, $qualifiedKey) {
            if (empty($value)) {
                return $query;
            }
            return $query->whereHas($relation, fn($q) => $q->whereIn($qualifiedKey, (array) $value));
        };
    }

    private function personOptions(): array
    {
        return Person::query()
            ->get()
            ->mapWithKeys(fn(Person $p) => [$p->id => $p->getFullName() ?: ('ID ' . $p->id)])
            ->toArray();
    }
}
