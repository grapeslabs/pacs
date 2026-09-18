<?php

namespace App\MoonShine\Resources;

use App\Jobs\PushKeysToControllers;
use App\Jobs\PushPersonsToControllers;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Support\Enums\ToastType;
use App\Models\GrapeslabsSkudController;
use App\Models\Key;
use App\Models\Person;
use App\Models\PersonPassageRule;
use App\Models\Tag;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomTextarea;
use App\MoonShine\Fields\SelectField;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

class PersonPassageRuleResource extends BaseModelResource
{
    protected string $model = PersonPassageRule::class;
    protected string $title = 'Правила прохода';
    protected array $with = ['people', 'personTags', 'keys', 'controllers'];
    protected string $column = 'name';

    private const TYPE_OPTIONS = [
        PersonPassageRule::TYPE_ALLOW => ['label' => 'Разрешить', 'color' => 'green'],
        PersonPassageRule::TYPE_DENY  => ['label' => 'Запретить', 'color' => 'red'],
    ];

    private array $oldControllerIds = [];
    private array|null $oldScope = null;
    private bool $syncQueued = false;


    public function deleteConfirmation(Model $item): array
    {
        return [
            'title' => 'Удалить правила прохода?',
            'text'  => '«' . ($item->name ?: 'Без имени') . '» будет удалена из базы.',
        ];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Наименование', 'name')->sortable(),
            Text::make('Персоны', 'people', fn($item) => $item->people
                ->map(fn(Person $p) => $p->getFullName())->implode(', ') ?: '-'),
            Text::make('Теги', 'personTags', fn($item) => $item->personTags->pluck('name')->implode(', ') ?: '-'),
            Text::make('Ключи', 'keys', fn($item) => $item->keys->pluck('key')->implode(', ') ?: '-'),
            Text::make('Контроллеры', 'controllers', fn($item) => $item->controllers
                ->map(fn($c) => $c->serial_number . ' (' . $c->type_label . ')')->implode(', ') ?: '-'),
            ColoredSelectField::make('Тип правила', 'type')->options(self::TYPE_OPTIONS),
            Switcher::make('Активно', 'is_active'),
            Text::make('Комментарий', 'comment'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            CustomText::make('Наименование', 'name')
                ->max(255, "Наименование не может содержать более 255 символов")
                ->placeholder('Введите наименование правила')
                ->required(),
            SelectField::make('Персоны (ФИО)', 'people')
                ->multiple()
                ->placeholder('Выберите персоны')
                ->options($this->personOptions()),
            SelectField::make('Теги персон', 'personTags')
                ->multiple()
                ->placeholder('Выберите теги персон')
                ->options(Tag::query()->pluck('name', 'id')->toArray()),
            SelectField::make('Ключи', 'keys')
                ->multiple()
                ->placeholder('Выберите ключи')
                ->options(Key::query()->pluck('key', 'id')->toArray()),
            SelectField::make('Контроллеры', 'controllers')
                ->multiple()
                ->required()
                ->placeholder('Выберите контроллеры')
                ->hint('Для распознавания лиц правило должно содержать персон или теги - правило только с ключами заблокирует проход по лицу. Ключи действуют только на RFID-контроллерах.')
                ->options($this->controllerOptions()),
            SelectField::make('Тип правила', 'type')
                ->required()
                ->default(PersonPassageRule::TYPE_ALLOW)
                ->placeholder('Выберите тип правила')
                ->options(PersonPassageRule::TYPES),
            Switcher::make('Активно', 'is_active')->default(true),
            CustomTextarea::make('Комментарий', 'comment')
                ->placeholder('Дополнительная информация')
                ->nullable(),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . PersonPassageRule::TYPE_ALLOW . ',' . PersonPassageRule::TYPE_DENY],
            'comment' => ['nullable', 'string'],
            'people' => ['nullable', 'array'],
            'people.*' => ['exists:person,id'],
            'personTags' => ['nullable', 'array'],
            'personTags.*' => ['exists:tags,id'],
            'keys' => ['nullable', 'array'],
            'keys.*' => ['exists:keys,id'],
            'controllers' => ['required', 'array', 'min:1'],
            'controllers.*' => ['exists:grapeslabs_skud_controllers,id'],
        ];
    }

    public function search(): array
    {
        return [
            'name',
            'comment',
            'personTags.name',
            'people.last_name',
            'people.first_name',
            'keys.key',
            'controllers.serial_number',
        ];
    }

    public function filters(): array
    {
        return [
            CustomText::make('Наименование', 'name')
                ->placeholder('Фильтрация по наименованию'),

            SelectField::make('Персоны', 'people_filter')
                ->multiple()->nullable()
                ->placeholder('Фильтрация по персонам')
                ->options($this->personOptions())
                ->onApply($this->relationFilter('people', 'person.id')),
            SelectField::make('Теги персон', 'tags_filter')
                ->multiple()->nullable()
                ->placeholder('Фильтрация по тегам персон')
                ->options(Tag::query()->pluck('name', 'id')->toArray())
                ->onApply($this->relationFilter('personTags', 'tags.id')),
            SelectField::make('Ключи', 'keys_filter')
                ->multiple()->nullable()
                ->placeholder('Фильтрация по ключам')
                ->options(Key::query()->pluck('key', 'id')->toArray())
                ->onApply($this->relationFilter('keys', 'keys.id')),
            SelectField::make('Контроллеры', 'controllers_filter')
                ->multiple()->nullable()
                ->placeholder('Фильтрация по контроллерам')
                ->options($this->controllerOptions())
                ->onApply($this->relationFilter('controllers', 'grapeslabs_skud_controllers.id')),
            SelectField::make('Тип правила', 'type')
                ->nullable()
                ->placeholder('Фильтрация по типу правила')
                ->options(PersonPassageRule::TYPES),
            CustomText::make('Комментарий', 'comment')
                ->placeholder('Фильтрация по комментарию'),
        ];
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

    private function controllerOptions(): array
    {
        return GrapeslabsSkudController::query()
            ->where('type', '!=', 'pingate')
            ->get()
            ->mapWithKeys(fn(GrapeslabsSkudController $c) => [$c->id => $c->serial_number . ' (' . $c->type_label . ')'])
            ->toArray();
    }

    protected function beforeUpdating(mixed $item): mixed
    {
        $this->oldControllerIds = $item->controllers()->get(['grapeslabs_skud_controllers.id'])->pluck('id')->all();
        $this->oldScope = $this->ruleScope($item);

        return $item;
    }

    protected function beforeDeleting(mixed $item): mixed
    {
        $this->oldControllerIds = $item->controllers()->get(['grapeslabs_skud_controllers.id'])->pluck('id')->all();
        $this->oldScope = $this->ruleScope($item);

        return $item;
    }

    protected function afterCreated(mixed $item): mixed
    {
        $fresh = $item->fresh();

        $this->dispatchPush(
            $this->ruleControllerIds($fresh),
            $this->mergeScopes([$this->ruleScope($fresh)]),
        );

        return $item;
    }

    protected function afterUpdated(mixed $item): mixed
    {
        $fresh = $item->fresh();

        $affected = collect($this->oldControllerIds)
            ->merge($this->ruleControllerIds($fresh))
            ->unique();

        $scope = $this->mergeScopes([$this->oldScope, $this->ruleScope($fresh)]);

        $this->oldControllerIds = [];
        $this->oldScope = null;

        $this->dispatchPush($affected, $scope);

        return $item;
    }

    protected function afterDeleted(mixed $item): mixed
    {
        $scope = $this->mergeScopes([$this->oldScope]);
        $controllerIds = collect($this->oldControllerIds);

        $this->oldControllerIds = [];
        $this->oldScope = null;

        $this->dispatchPush($controllerIds, $scope);

        return parent::afterDeleted($item);
    }

    private function ruleControllerIds(PersonPassageRule $rule): Collection
    {
        return $rule->controllers()->get(['grapeslabs_skud_controllers.id'])->pluck('id');
    }

    /**
     * Субъекты правила: null в ветке означает «затронуты все» — правило без субъектов
     * матчит каждого, поэтому пересчитывать приходится всю базу.
     */
    private function ruleScope(PersonPassageRule $rule): array
    {
        $rule->loadMissing(['keys', 'people', 'personTags.persons']);

        if (! $rule->hasSubjects()) {
            return ['keys' => null, 'persons' => null];
        }

        $personIds = $rule->people->pluck('id')
            ->merge($rule->personTags->flatMap(fn ($tag) => $tag->persons->pluck('id')))
            ->unique()
            ->values();

        $keyIds = $rule->keys->pluck('id')
            ->merge(Key::query()->whereIn('person_id', $personIds)->pluck('id'))
            ->unique()
            ->values();

        return ['keys' => $keyIds->all(), 'persons' => $personIds->all()];
    }

    private function mergeScopes(array $scopes): array
    {
        $keys = [];
        $persons = [];

        foreach (array_filter($scopes) as $scope) {
            if ($scope['keys'] === null || $scope['persons'] === null) {
                return ['keys' => null, 'persons' => null];
            }

            $keys = array_merge($keys, $scope['keys']);
            $persons = array_merge($persons, $scope['persons']);
        }

        return [
            'keys' => array_values(array_unique($keys)),
            'persons' => array_values(array_unique($persons)),
        ];
    }

    /**
     * Смена «управляемости» контроллера требует полного прогона именно по нему:
     * до первого правила он пускал всех, после удаления последнего — снова будет.
     */
    private function managementChanged(Collection $controllerIds): bool
    {
        $managed = PersonPassageRule::query()
            ->where('is_active', true)
            ->with('controllers')
            ->get()
            ->flatMap(fn (PersonPassageRule $rule) => $rule->controllers->pluck('id'))
            ->unique();

        return $controllerIds->contains(fn ($id) => ! $managed->contains($id));
    }

    private function dispatchPush(Collection $controllerIds, array $scope): void
    {
        $controllerIds = $controllerIds->unique()->filter()->values();

        if ($controllerIds->isEmpty()) {
            return;
        }

        if ($this->managementChanged($controllerIds)) {
            $scope = ['keys' => null, 'persons' => null];
        }

        $userId = MoonShineAuth::getGuard()->id();
        $ids = $controllerIds->all();

        PushKeysToControllers::dispatch($ids, $scope['keys'], $userId)
            ->onConnection('redis-sync')
            ->delay(now()->addSeconds(10));

        PushPersonsToControllers::dispatch($ids, $scope['persons'], $userId)
            ->onConnection('redis-sync')
            ->delay(now()->addSeconds(10));

        $this->syncQueued = true;
    }

    public function modifySaveResponse(MoonShineJsonResponse $response): MoonShineJsonResponse
    {
        if ($this->syncQueued) {
            $response->toast(
                'Сохранено. Синхронизация запущена, изменения применятся в течение минуты',
                ToastType::SUCCESS,
            );
        }

        return $response;
    }
}

