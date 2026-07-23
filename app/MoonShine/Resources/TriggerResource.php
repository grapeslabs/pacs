<?php

namespace App\MoonShine\Resources;

use App\Models\Bot;
use App\Models\Stream;
use App\Models\Tag;
use App\Models\Trigger;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Fields\TriggerContentField;
use Closure;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\Collection\TableRowsContract;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Json;

class TriggerResource extends BaseModelResource
{
    protected string $model = Trigger::class;
    protected string $title = 'Триггеры';

    public function indexSubtitle(): ?string
    {
        return $this->countSubtitle('триггер', 'триггера', 'триггеров');
    }
    protected ?string $editModalTitle = 'Редактирование триггера';
    protected string $column = 'name';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить триггер';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW);
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend($this->testMessageButton());
    }

    /** Кнопка-иконка «Отправить тестовое сообщение» через бота триггера. */
    protected function testMessageButton(): \MoonShine\Contracts\UI\ActionButtonContract
    {
        $plane = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 4 3 11l6 2 2 6 4-6 6-9z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>';

        return ActionButton::make('', fn($item) => $this->getAsyncMethodUrl('sendTest', params: ['item_id' => $item->getKey()]))
            ->icon($plane, true)
            ->class('js-test-button')
            ->customAttributes([
                'title' => 'Отправить тестовое сообщение',
                '@click.stop' => '',
            ])
            ->async()
            ->canSee(fn($item) => (bool) $item->bot_id);
    }

    public function sendTest(MoonShineRequest $request): MoonShineJsonResponse
    {
        $trigger = Trigger::query()->with('bot')->find($request->get('item_id'));

        if (! $trigger || ! $trigger->bot) {
            return MoonShineJsonResponse::make()->toast('Бот не привязан к триггеру', ToastType::ERROR);
        }

        try {
            $trigger->bot->sendBroadcast('Тестовое сообщение от триггера «' . $trigger->name . '»');

            return MoonShineJsonResponse::make()->toast('Тестовое сообщение отправлено', ToastType::SUCCESS);
        } catch (\Throwable $e) {
            return MoonShineJsonResponse::make()->toast('Ошибка отправки: ' . $e->getMessage(), ToastType::ERROR);
        }
    }

    public function detailSidebarData(Model $item): array
    {
        $item->loadMissing('bot');

        $icBolt = $this->iconFile('lightning');
        $icBot = $this->iconFile('bot');
        $icGear = $this->iconFile('gear');

        $deviceLabel = $this->deviceTypeLabel($item->device_type);
        $deviceExists = $item->device_type === Trigger::DEVICE_CAMERA
            ? (bool) Stream::find($item->device_id)
            : (bool) SkudController::find($item->device_id);
        $deviceName = match ($item->device_type) {
            Trigger::DEVICE_CAMERA => Stream::find($item->device_id)?->name,
            default => SkudController::find($item->device_id)?->serial_number,
        } ?? 'Устройство удалено';

        $active = (bool) $item->is_active;

        $deviceValue = $deviceExists ? trim($deviceName . ' · ' . $deviceLabel) : $deviceName;

        $sections = [
            [
                'icon' => $icGear,
                'title' => 'Конфигурация',
                'single' => true,
                'fields' => [
                    ['label' => 'Устройство', 'full' => true, 'value' => $deviceValue],
                    ['label' => 'Событие', 'full' => true, 'value' => $this->eventLabel($item->event_type)],
                    ['label' => 'Бот', 'full' => true, 'value' => $item->bot?->name ?? '—'],
                ],
            ],
        ];

        $badges = [
            $active
                ? ['text' => 'Включён']
                : ['text' => 'Выключен', 'tone' => 'muted'],
        ];
        if ($item->bot) {
            $badges[] = [
                'text' => $item->bot->name,
                'icon' => $icBot,
                'url' => $this->filteredIndexUrl(app(BotResource::class), ['name' => $item->bot->name]),
            ];
        }

        return [
            'title' => 'Карточка триггера',
            'subtitle' => 'Триггер · ' . $deviceLabel,
            'hero' => [
                'title' => $item->name,
                'subtitle' => 'Триггер · ' . $deviceLabel,
                'icon' => $icBolt,
                'badges' => $badges,
            ],
            'sections' => $sections,
            'footer' => [
                ['spacer' => true],
                ['html' => $this->detailEditButtonHtml($item)],
            ],
        ];
    }

    private function deviceTypeLabel(?string $type): string
    {
        return [
            Trigger::DEVICE_CAMERA => 'Камера',
            Trigger::DEVICE_TERMINAL => 'Терминал доступа',
            Trigger::DEVICE_BARIER => 'Шлагбаум',
            Trigger::DEVICE_CONTROLLER => 'Контроллер СКУД',
        ][$type] ?? (string) $type;
    }

    private function eventLabel(?string $type): string
    {
        return [
            Trigger::EVENT_KNOWED => 'Появление известного лица',
            Trigger::EVENT_UNKNOWED => 'Появление неизвестного лица',
            Trigger::EVENT_INCOME => 'Вход',
            Trigger::EVENT_OUTCOME => 'Выход',
            Trigger::EVENT_MANUAL => 'Открыто вручную',
            Trigger::EVENT_DENIED => 'Отказано',
        ][$type] ?? (string) $type;
    }

    public function deleteConfirmation(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'title' => 'Удалить триггер?',
            'text'  => '«' . ($item->name ?: 'Триггер') . '» будет удалён.',
        ];
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('Название', 'name')
                ->sortable(),

            AvatarField::make('Устройство', 'device_id')
                ->icon(fn($item) => $this->deviceIconSvg($item->device_type))
                ->text(fn($item) => $this->resolveDevice($item)['name'] ?: ('Устройство #' . $item->device_id))
                ->subtext(fn($item) => $this->resolveDevice($item)['exists'] ? $this->deviceTypeLabel($item->device_type) : 'Устройство удалено')
                ->deleted(fn($item) => ! $this->resolveDevice($item)['exists']),

            ColoredSelectField::make('Событие', 'event_type', fn($item) => $item->event_type)
                ->options([
                    Trigger::EVENT_KNOWED   => ['label' => 'Появление известного лица', 'color' => 'green'],
                    Trigger::EVENT_UNKNOWED => ['label' => 'Появление неизвестного лица', 'color' => 'yellow'],
                    Trigger::EVENT_INCOME   => ['label' => 'Вход', 'color' => 'gray'],
                    Trigger::EVENT_OUTCOME  => ['label' => 'Выход', 'color' => 'gray'],
                    Trigger::EVENT_MANUAL   => ['label' => 'Открыто вручную', 'color' => 'blue'],
                    Trigger::EVENT_DENIED   => ['label' => 'Отказано', 'color' => 'red'],
                ])
                ->sortable(fn (Builder $query, string $column, string $direction) => $query->orderByRaw("
                        CASE $column
                            WHEN '" . Trigger::EVENT_INCOME . "' THEN 'Вход'
                            WHEN '" . Trigger::EVENT_OUTCOME . "' THEN 'Выход'
                            WHEN '" . Trigger::EVENT_DENIED . "' THEN 'Отказано'
                            WHEN '" . Trigger::EVENT_MANUAL . "' THEN 'Открыто вручную'
                            WHEN '" . Trigger::EVENT_KNOWED . "' THEN 'Появление известного лица'
                            WHEN '" . Trigger::EVENT_UNKNOWED . "' THEN 'Появление неизвестного лица'
                            ELSE $column
                        END $direction
                    ")),

            AvatarField::make('Бот', 'bot_id')
                ->icon($this->iconFile('bot'))
                ->text(fn($item) => $item->bot?->name ?? '—'),

            Switcher::make('Активность', 'is_active')
                ->updateOnPreview()
                ->sortable(),
        ];
    }

    private array $deviceCache = [];

    private function resolveDevice($item): array
    {
        $key = $item->device_type . ':' . $item->device_id;
        if (! isset($this->deviceCache[$key])) {
            if ($item->device_type === Trigger::DEVICE_CAMERA) {
                $model = Stream::find($item->device_id);
                $name = $model?->name;
            } else {
                $model = SkudController::find($item->device_id);
                $name = $model?->serial_number;
            }
            $this->deviceCache[$key] = ['exists' => (bool) $model, 'name' => $name];
        }

        return $this->deviceCache[$key];
    }

    private function deviceIconSvg(?string $type): string
    {
        $file = match ($type) {
            Trigger::DEVICE_CAMERA   => 'menu-video.svg',
            Trigger::DEVICE_TERMINAL => 'terminal.svg',
            Trigger::DEVICE_BARIER   => 'gate.svg',
            default                  => 'controller.svg',
        };

        return @file_get_contents(public_path('icons/' . $file)) ?: '';
    }

    public function formFields(): iterable
    {
        $item = $this->getItem();
        $magnifierIcon = file_get_contents(public_path('icons/magnifier.svg'));

        return [
            ID::make(),

            CustomText::make('Название', 'name')
                ->unique('triggers', 'name', 'Название триггера должно быть уникальным')
                ->required(),

            SelectField::make('Тип устройства', 'device_type')
                ->options([
                    Trigger::DEVICE_CAMERA => 'Камера',
//                    Trigger::DEVICE_TERMINAL => 'Терминал доступа',
//                    Trigger::DEVICE_BARIER => 'Шлагбаум',
//                    Trigger::DEVICE_CONTROLLER => 'Контроллер СКУД',
                ])
                ->customAttributes([
                    '@change' => "
                        let evType = document.querySelector('select[name=\"event_type\"]');
                        let evTypeCam = document.querySelector('select[name=\"event_type_camera\"]');
                        if(evType) { evType.value = ''; evType.dispatchEvent(new Event('change')); }
                        if(evTypeCam) { evTypeCam.value = ''; evTypeCam.dispatchEvent(new Event('change')); }
                    "
                ]),

            SelectField::make('Устройство', 'device_id_camera')
                ->options(Stream::query()->pluck('name', 'id')->toArray())
                ->showWhen('device_type', '=', Trigger::DEVICE_CAMERA)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null)
                ->icon($magnifierIcon),

            SelectField::make('Устройство', 'device_id_terminal')
                ->options(SkudController::query()->where('type', 'pinterm')->pluck('serial_number', 'id')->toArray())
                ->showWhen('device_type', '=', Trigger::DEVICE_TERMINAL)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null)
                ->icon($magnifierIcon),

            SelectField::make('Устройство', 'device_id_barier')
                ->options(SkudController::query()->where('type', 'pingate')->pluck('serial_number', 'id')->toArray())
                ->showWhen('device_type', '=', Trigger::DEVICE_BARIER)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null)
                ->icon($magnifierIcon),

            SelectField::make('Устройство', 'device_id_controller')
                ->options(SkudController::query()->whereIn('type', ['z5rweb', 'ironlogic'])->pluck('serial_number', 'id')->toArray())
                ->showWhen('device_type', '=', Trigger::DEVICE_CONTROLLER)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null)
                ->icon($magnifierIcon),


            SelectField::make('Событие', 'event_type')
                ->options([
                    Trigger::EVENT_INCOME => 'Вход',
                    Trigger::EVENT_OUTCOME => 'Выход',
                    Trigger::EVENT_MANUAL => 'Открыто вручную',
                    Trigger::EVENT_DENIED => 'Отказано',
                ])
                ->showWhen('device_type', '!=', Trigger::DEVICE_CAMERA)
                ->onApply(function ($item, $value) {
                    if (request('device_type') != Trigger::DEVICE_CAMERA && $value) {
                        $item->event_type = $value;
                    }
                    return $item;
                }),

            SelectField::make('Событие', 'event_type_camera')
                ->options([
                    Trigger::EVENT_KNOWED => 'Появление известного лица',
                    Trigger::EVENT_UNKNOWED => 'Появление неизвестного лица',
                ])
                ->showWhen('device_type', '=', Trigger::DEVICE_CAMERA)
                ->onApply(function ($item, $value) {
                    if (request('device_type') == Trigger::DEVICE_CAMERA && $value) {
                        $item->event_type = $value;
                    }
                    return $item;
                })
                ->setValue($item->event_type ?? null),

            SelectField::make('Теги', 'tags')
                ->icon('tag')
                ->optionsIcon('tag')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true),

            SelectField::make('Бот', 'bot_id')
                ->options(Bot::query()->pluck('name', 'id')->toArray())
                ->required()
                ->icon($magnifierIcon),

            TriggerContentField::make('Содержимое уведомления', 'data')
                ->withoutWrapper()
                ->onApply(function ($item) {
                    $item->data = TriggerContentField::normalize(request('data', []));
                    return $item;
                }),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function search(): array
    {
        return [
            'id',
            'name',
            'bot.name'
        ];
    }

    public function filters(): array
    {
        return [
            ID::make(),
            Text::make('Название', 'name')
                ->placeholder('Фильтрация по названию')
                ->nullable(),

            SelectField::make('Тип устройства', 'device_type')
                ->placeholder('Фильтрация по типу устройства')
                ->nullable()
                ->options([
                    Trigger::DEVICE_CAMERA => 'Камера',
                    Trigger::DEVICE_TERMINAL => 'Терминал доступа',
                    Trigger::DEVICE_BARIER => 'Шлагбаум',
                    Trigger::DEVICE_CONTROLLER => 'Контроллер СКУД',
                ]),

            Switcher::make('Активность', 'is_active'),

            SelectField::make('Бот', 'bot_id')
                ->options(Bot::query()->pluck('name', 'id')->toArray())
                ->placeholder('Фильтрация по боту')
                ->nullable(),

            SelectField::make('Событие', 'event_type')
                ->options([
                    Trigger::EVENT_UNKNOWED => 'Неизвестное лицо',
                    Trigger::EVENT_KNOWED => 'Известное лицо',
                    Trigger::EVENT_INCOME => 'Вход',
                    Trigger::EVENT_OUTCOME => 'Выход',
                    Trigger::EVENT_MANUAL => 'Открыто вручную',
                    Trigger::EVENT_DENIED => 'Отказано',
                ])
                ->placeholder('Фильтрация по типу')
                ->nullable(),

            SelectField::make('Теги', 'tags')
                ->icon('tag')
                ->optionsIcon('tag')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
        ];
    }
}
