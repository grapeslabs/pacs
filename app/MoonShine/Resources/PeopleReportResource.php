<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Fields\SelectField;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Handlers\Handler;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class PeopleReportResource extends BaseModelResource
{
    protected string $model = VideoAnalyticReport::class;
    protected string $title = 'Отчеты по персонам';
    protected string $column = 'name';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::DESC;

    protected ?string $oldPerson = null;

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only();
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where('is_unknown', false);
    }

    public function query(): Builder
    {
        return parent::query()->with(['person', 'stream']);
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('Персона', 'person_photobank_id', function ($item) {
                if ($this->oldPerson === $item->person_photobank_id) {
                    return "";
                }
                return $item->person?->getFullName() ?? $item->person_photobank_id;
            }),

            Date::make('Дата рождения', null, function ($item) {
                if ($this->oldPerson === $item->person_photobank_id) {
                    return "";
                }
                return $item->person?->birth_date;
            })->format('d.m.Y'),

            Text::make('Теги', null, function ($item) {
                if ($this->oldPerson === $item->person_photobank_id) {
                    return "";
                }
                $this->oldPerson = $item->person_photobank_id;
                return $item->person?->tags_list ?? '';
            }),

            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),

            BelongsTo::make('Камера','stream',fn($item)=>$item->name??'Камера удалена', VideoStreamResource::class)
                ->sortable(),

            Image::make('Фото', null, fn($item) => basename($item->data['snapshot_path'] ?? ''))
                ->disk('analytic')
                ->dir('thumbnails')
                ->setLabel('Фото'),
        ];
    }

    public function search(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Период отчётности', 'datetime')
                ->withTime(),

            SelectField::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable(),

            SelectField::make('Персона', 'person_photobank_id')
                ->placeholder('Персона')
                ->options(Person::query()->limit(500)->pluck('last_name', 'grapesva_uuid')->toArray())
                ->nullable()
        ];
    }

    protected array $personCache = [];
    protected array $cameraCache = [];

    protected function getPerson($id)
    {
        if (empty($id)) return null;
        if (!array_key_exists($id, $this->personCache)) {
            $this->personCache[$id] = Person::where('grapesva_uuid', $id)->first();
        }
        return $this->personCache[$id];
    }

    protected function getCamera($id)
    {
        if (empty($id)) return null;
        if (!array_key_exists($id, $this->cameraCache)) {
            $this->cameraCache[$id] = Stream::where('uid', $id)->first();
        }
        return $this->cameraCache[$id];
    }

    protected function exportFields(): iterable
    {
        return [
            Text::make('Персона', 'person_photobank_id')
                ->changeFill(function ($item) {
                    if (empty($item->person_photobank_id)) {
                        return 'Неизвестная персона';
                    }
                    $person = $this->getPerson($item->person_photobank_id);
                    return $person
                        ? "[$item->person_photobank_id] " . $person->getfullname()
                        : "[$item->person_photobank_id] Нет в БД";
                }),

            Date::make('Дата рождения', 'person_photobank_id')
                ->changeFill(function ($item) {
                    if (empty($item->person_photobank_id)) {
                        return 'Данные не найдены';
                    }
                    return $this->getPerson($item->person_photobank_id)?->birth_date;
                })->format('d.m.Y'),

            Text::make('Теги', 'person_photobank_id')
                ->changeFill(function ($item) {
                    if (empty($item->person_photobank_id)) {
                        return null;
                    }
                    return $this->getPerson($item->person_photobank_id)?->tags_list;
                }),

            Date::make('Дата и время', 'datetime')
                ->withTime(),

            Text::make('Камера', 'camera_id')
                ->changeFill(function ($item) {
                    return $this->getCamera($item->camera_id)?->name ?? 'Неизвестная камера';
                }),
        ];
    }
}
