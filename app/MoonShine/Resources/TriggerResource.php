<?php

namespace App\MoonShine\Resources;

use App\Models\Bot;
use App\Models\Stream;
use App\Models\Tag;
use App\Models\Trigger;
use App\MoonShine\Fields\Select2Field;
use App\MoonShine\Fields\SelectField;
use Closure;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Contracts\Database\Eloquent\Builder;
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
    protected string $column = 'name';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Text::make('Название', 'name')
                ->sortable(),

            Select::make('Тип устройства', 'device_type')
                ->options([
                    Trigger::DEVICE_CAMERA => 'Камера',
                    Trigger::DEVICE_TERMINAL => 'Терминал доступа',
                    Trigger::DEVICE_BARIER => 'Шлагбаум',
                    Trigger::DEVICE_CONTROLLER => 'Контроллер СКУД',
                ])
                ->sortable(fn (Builder $query, string $column, string $direction) => $query->orderByRaw("
                    CASE $column
                        WHEN '" . Trigger::DEVICE_CAMERA . "' THEN 'Камера'
                        WHEN '" . Trigger::DEVICE_CONTROLLER . "' THEN 'Контроллер СКУД'
                        WHEN '" . Trigger::DEVICE_TERMINAL . "' THEN 'Терминал доступа'
                        WHEN '" . Trigger::DEVICE_BARIER . "' THEN 'Шлагбаум'
                        ELSE $column
                    END $direction
                ")),

            Text::make('Устройство', 'device_id', function ($item) {
                return match($item->device_type) {
                    Trigger::DEVICE_CAMERA => Stream::find($item->device_id)->name??'Устройство удалено',
                    default => SkudController::find($item->device_id)?->serial_number,
                };
            }),

            Select::make('Событие', 'event_type')
                ->options([
                    Trigger::EVENT_KNOWED => 'Появление известного лица',
                    Trigger::EVENT_UNKNOWED => 'Появление неизвестного лица',
                    Trigger::EVENT_INCOME => 'Вход',
                    Trigger::EVENT_OUTCOME => 'Выход',
                    Trigger::EVENT_MANUAL => 'Открыто вручную',
                    Trigger::EVENT_DENIED => 'Отказано',
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

            Switcher::make('Активность', 'is_active')
                ->updateOnPreview()
                ->sortable(),

            BelongsTo::make('Бот', 'bot', 'name', resource: BotResource::class)
                ->sortable(function (Builder $query, string $column, string $direction) {
                    return $query->orderBy(
                        Bot::select('name')
                            ->whereColumn('bots.id', 'triggers.bot_id')
                            ->limit(1),
                        $direction
                    );
                })
        ];
    }

    public function formFields(): iterable
    {
        $item = $this->getItem();

        return [
            ID::make(),
            Text::make('Название', 'name')
                ->required(),

            Select::make('Тип устройства', 'device_type')
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

            Select::make('Устройство', 'device_id_camera')
                ->options(Stream::query()->pluck('name', 'id')->toArray())
                ->searchable()
                ->showWhen('device_type', '=', Trigger::DEVICE_CAMERA)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null),

            Select::make('Устройство', 'device_id_terminal')
                ->options(SkudController::query()->where('type', 'pinterm')->pluck('serial_number', 'id')->toArray())
                ->searchable()
                ->showWhen('device_type', '=', Trigger::DEVICE_TERMINAL)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null),

            Select::make('Устройство', 'device_id_barier')
                ->options(SkudController::query()->where('type', 'pingate')->pluck('serial_number', 'id')->toArray())
                ->searchable()
                ->showWhen('device_type', '=', Trigger::DEVICE_BARIER)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null),

            Select::make('Устройство', 'device_id_controller')
                ->options(SkudController::query()->whereIn('type', ['z5rweb', 'ironlogic'])->pluck('serial_number', 'id')->toArray())
                ->searchable()
                ->showWhen('device_type', '=', Trigger::DEVICE_CONTROLLER)
                ->onApply(function ($item, $value) {
                    if ($value) {
                        $item->device_id = $value;
                    }
                    return $item;
                })
                ->setValue($item->device_id ?? null),

            Select::make('Событие', 'event_type')
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

            Select::make('Событие', 'event_type_camera')
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

            Select::make('Бот', 'bot_id')
                ->options(Bot::query()->pluck('name', 'id')->toArray())
                ->required()
                ->searchable(),

            Checkbox::make('Дата', 'data->date')
                ->setValue((boolean)$item?->data['date'] ?? false),
            Checkbox::make('Время', 'data->time')
                ->setValue((boolean)$item?->data['time'] ?? false),

            Checkbox::make('Видеопоток', 'data->stream')
                ->setValue((boolean)$item?->data['stream'] ?? false)
                ->showWhen('device_type', '=', Trigger::DEVICE_CAMERA),

            Checkbox::make('ФИО', 'data->lfm')
                ->setValue((boolean)$item?->data['lfm'] ?? false)
                ->showWhen('event_type_camera', '!=', Trigger::EVENT_UNKNOWED)
                ->showWhen('event_type', '!=', Trigger::EVENT_MANUAL)
                ->showWhen('event_type', '!=', Trigger::EVENT_DENIED),

            Checkbox::make('ID персоны', 'data->person_id')
                ->setValue((boolean)$item?->data['person_id'] ?? false)
                ->showWhen('event_type_camera', '!=', Trigger::EVENT_UNKNOWED)
                ->showWhen('event_type', '!=', Trigger::EVENT_MANUAL)
                ->showWhen('event_type', '!=', Trigger::EVENT_DENIED),

            Checkbox::make('Фото из фотобонка', 'data->photobank')
                ->setValue((boolean)$item?->data['photobank'] ?? false)
                ->showWhen('event_type_camera', '!=', Trigger::EVENT_UNKNOWED)
                ->showWhen('event_type', '!=', Trigger::EVENT_MANUAL)
                ->showWhen('event_type', '!=', Trigger::EVENT_DENIED),

            Checkbox::make('Комментарий', 'data->comment')
                ->setValue((boolean)$item?->data['comment'] ?? false)
                ->showWhen('event_type_camera', '!=', Trigger::EVENT_UNKNOWED)
                ->showWhen('event_type', '!=', Trigger::EVENT_MANUAL)
                ->showWhen('event_type', '!=', Trigger::EVENT_DENIED),

            Checkbox::make('Фото камеры', 'data->photo')
                ->setValue((boolean)$item?->data['photo'] ?? false)
                ->showWhen('device_type', '=', Trigger::DEVICE_CAMERA),

            SelectField::make('Теги', 'tags')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
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

            Select::make('Тип устройства', 'device_type')
                ->placeholder('Фильтрация по типу устройства')
                ->searchable()
                ->nullable()
                ->options([
                    Trigger::DEVICE_CAMERA => 'Камера',
                    Trigger::DEVICE_TERMINAL => 'Терминал доступа',
                    Trigger::DEVICE_BARIER => 'Шлагбаум',
                    Trigger::DEVICE_CONTROLLER => 'Контроллер СКУД',
                ]),

            Switcher::make('Активность', 'is_active'),

            Select::make('Бот', 'bot_id')
                ->options(Bot::query()->pluck('name', 'id')->toArray())
                ->placeholder('Фильтрация по боту')
                ->nullable()
                ->searchable(),

            Select::make('Событие', 'event_type')
                ->options([
                    Trigger::EVENT_UNKNOWED => 'Неизвестное лицо',
                    Trigger::EVENT_KNOWED => 'Известное лицо',
                    Trigger::EVENT_INCOME => 'Вход',
                    Trigger::EVENT_OUTCOME => 'Выход',
                    Trigger::EVENT_MANUAL => 'Открыто вручную',
                    Trigger::EVENT_DENIED => 'Отказано',
                ])
                ->placeholder('Фильтрация по типу')
                ->nullable()
                ->searchable(),

            SelectField::make('Теги', 'tags')
                ->options(Tag::select('id', 'name')->get())
                ->multiple(true)
        ];
    }
}
