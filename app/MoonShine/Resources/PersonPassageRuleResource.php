<?php

namespace App\MoonShine\Resources;

use App\Models\GrapeslabsSkudController;
use App\Models\Key;
use App\Models\Person;
use App\Models\PersonPassageRule;
use App\Models\Tag;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomTextarea;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use App\Observers\PersonObserver;
use App\Services\ControllerAccessService;
use GrapesLabs\PinvideoSkud\ControllerFactory;
use GrapesLabs\PinvideoSkud\Keys\FaceIdKey;
use GrapesLabs\PinvideoSkud\Keys\RfidKey;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

class PersonPassageRuleResource extends BaseModelResource
{
    protected string $model = PersonPassageRule::class;
    protected string $title = 'Правила прохода';
    protected string $column = 'name';

    private const TYPE_OPTIONS = [
        PersonPassageRule::TYPE_ALLOW => ['label' => 'Разрешить', 'color' => 'green'],
        PersonPassageRule::TYPE_DENY  => ['label' => 'Запретить', 'color' => 'red'],
    ];

    private ControllerAccessService $access;

    private array $oldControllerIds = [];

    public function __construct(CoreContract $core, ControllerAccessService $access)
    {
        parent::__construct($core);
        $this->access = $access;
    }

    protected function pages(): array
    {
        return [CustomIndexPage::class, DetailPage::class, FormPage::class];
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
                ->required(),
            SelectField::make('Персоны (ФИО)', 'people')
                ->multiple()
                ->options($this->personOptions()),
            SelectField::make('Теги персон', 'personTags')
                ->multiple()
                ->options(Tag::query()->pluck('name', 'id')->toArray()),
            SelectField::make('Ключи', 'keys')
                ->multiple()
                ->options(Key::query()->pluck('key', 'id')->toArray()),
            SelectField::make('Контроллеры', 'controllers')
                ->multiple()
                ->required()
                ->hint('Для распознавания лиц правило должно содержать персон или теги - правило только с ключами заблокирует проход по лицу. Ключи действуют только на RFID-контроллерах.')
                ->options($this->controllerOptions()),
            SelectField::make('Тип правила', 'type')
                ->required()
                ->default(PersonPassageRule::TYPE_ALLOW)
                ->options(PersonPassageRule::TYPES),
            Switcher::make('Активно', 'is_active')->default(true),
            CustomTextarea::make('Комментарий', 'comment')->nullable(),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Наименование', 'name'),
            Text::make('Персоны', 'people', fn($item) => $item->people->map(fn(Person $p) => $p->getFullName())->implode(', ') ?: '—'),
            Text::make('Теги', 'personTags', fn($item) => $item->personTags->pluck('name')->implode(', ') ?: '—'),
            Text::make('Ключи', 'keys', fn($item) => $item->keys->pluck('key')->implode(', ') ?: '—'),
            Text::make('Контроллеры', 'controllers', fn($item) => $item->controllers->map(fn($c) => $c->serial_number . ' (' . $c->type_label . ')')->implode(', ') ?: '—'),
            ColoredSelectField::make('Тип правила', 'type')->options(self::TYPE_OPTIONS),
            Switcher::make('Активно', 'is_active'),
            Text::make('Комментарий', 'comment'),
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
            CustomText::make('Наименование', 'name'),

            SelectField::make('Персоны', 'people_filter')
                ->multiple()->nullable()
                ->options($this->personOptions())
                ->onApply($this->relationFilter('people', 'person.id')),
            SelectField::make('Теги персон', 'tags_filter')
                ->multiple()->nullable()
                ->options(Tag::query()->pluck('name', 'id')->toArray())
                ->onApply($this->relationFilter('personTags', 'tags.id')),
            SelectField::make('Ключи', 'keys_filter')
                ->multiple()->nullable()
                ->options(Key::query()->pluck('key', 'id')->toArray())
                ->onApply($this->relationFilter('keys', 'keys.id')),
            SelectField::make('Контроллеры', 'controllers_filter')
                ->multiple()->nullable()
                ->options($this->controllerOptions())
                ->onApply($this->relationFilter('controllers', 'grapeslabs_skud_controllers.id')),
            SelectField::make('Тип правила', 'type')
                ->nullable()
                ->options(PersonPassageRule::TYPES),
            CustomText::make('Комментарий', 'comment'),
        ];
    }

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->with(['people', 'personTags', 'keys', 'controllers']);
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

        return $item;
    }

    protected function beforeDeleting(mixed $item): mixed
    {
        $this->oldControllerIds = $item->controllers()->get(['grapeslabs_skud_controllers.id'])->pluck('id')->all();

        return $item;
    }

    protected function afterCreated(mixed $item): mixed
    {
        $this->reconcileControllers($this->ruleControllerIds($item->fresh()));

        return $item;
    }

    protected function afterUpdated(mixed $item): mixed
    {
        $affected = collect($this->oldControllerIds)
            ->merge($this->ruleControllerIds($item->fresh()))
            ->unique();
        $this->oldControllerIds = [];

        $this->reconcileControllers($affected);

        return $item;
    }

    protected function afterDeleted(mixed $item): mixed
    {
        $this->reconcileControllers(collect($this->oldControllerIds));
        $this->oldControllerIds = [];

        return $item;
    }

    private function ruleControllerIds(PersonPassageRule $rule): Collection
    {
        return $rule->controllers()->get(['grapeslabs_skud_controllers.id'])->pluck('id');
    }

    private function reconcileControllers(Collection $controllerIds): void
    {
        $controllerIds = $controllerIds->unique()->filter()->values();
        if ($controllerIds->isEmpty()) {
            return;
        }
        $this->access->flush();
        $rfidTargets = GrapeslabsSkudController::whereIn('id', $controllerIds)
            ->whereNotIn('type', ['pingate', 'pinterm'])->get();
        $faceTargets = GrapeslabsSkudController::whereIn('id', $controllerIds)
            ->where('type', 'pinterm')->get();
        $personObserver = app(PersonObserver::class);

        if ($rfidTargets->isNotEmpty()) {
            Key::query()->with('person.tags')->chunkById(200, function ($keys) use ($rfidTargets, $personObserver) {
                foreach ($keys as $key) {
                    $frozen = $key->person
                        && $personObserver->isCurrentlyFrozen($key->person->frozen_start, $key->person->frozen_end);
                    $allowedIds = $frozen ? collect() : $this->access->rfidControllersForKey($key)->pluck('id');
                    $rfidKey = new RfidKey(code: $key->key, type: $key->type);

                    foreach ($rfidTargets as $controller) {
                        $this->applyKey($controller, $rfidKey, $allowedIds->contains($controller->id), "rule resync rfid key {$key->id}");
                    }
                }
            });
        }

        if ($faceTargets->isNotEmpty()) {
            $faceTargetIds = $faceTargets->pluck('id');
            Person::query()->with('tags')->chunkById(200, function ($people) use ($faceTargets, $faceTargetIds, $personObserver) {
                foreach ($people as $person) {
                    $frozen = $personObserver->isCurrentlyFrozen($person->frozen_start, $person->frozen_end);
                    $allowedIds = $frozen ? collect() : $this->access->faceControllersForPerson($person)->pluck('id');
                    $needImages = $allowedIds->intersect($faceTargetIds)->isNotEmpty();

                    $faceKey = new FaceIdKey(
                        uid: $person->key_uid ?? $person->getSkudUid(),
                        images: $needImages ? $this->imagesData($person->photo) : [],
                        name: $person->getFullName(),
                    );

                    foreach ($faceTargets as $controller) {
                        $this->applyKey($controller, $faceKey, $allowedIds->contains($controller->id), "rule resync face person {$person->id}");
                    }
                }
            });
        }

        Person::query()->whereNotNull('frozen_start')->chunkById(200, function ($people) use ($personObserver) {
            foreach ($people as $person) {
                $personObserver->scheduleCommands($person);
            }
        });
    }

    private function applyKey(GrapeslabsSkudController $controller, $key, bool $allowed, string $context): void
    {
        try {
            $vendor = new SkudController();
            $vendor->setRawAttributes($controller->getAttributes(), true);
            $vendor->exists = $controller->exists;
            $skud = ControllerFactory::create($vendor);

            if ($allowed) {
                $skud->writeKeys([$key]);
            } else {
                $skud->clearKeys([$key]);
            }
        } catch (\Exception $e) {
            Log::error("SKUD $context exception on controller {$controller->serial_number}: " . $e->getMessage());
        }
    }

    private function imagesData(?array $photos): array
    {
        if (empty($photos)) {
            return [];
        }
        $images = [];
        foreach ($photos as $photoPath) {
            if (Storage::disk('public')->exists($photoPath)) {
                $images[] = [
                    'id' => pathinfo($photoPath, PATHINFO_FILENAME),
                    'content' => Storage::disk('public')->get($photoPath),
                ];
            }
        }
        return $images;
    }
}
