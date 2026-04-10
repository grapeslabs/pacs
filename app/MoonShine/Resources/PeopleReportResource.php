<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
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

            Select::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable()
                ->searchable(),

            Select::make('Персона', 'person_photobank_id')
                ->placeholder('Персона')
                ->options(Person::query()->limit(500)->pluck('last_name', 'grapesva_uuid')->toArray())
                ->nullable()
                ->searchable()
        ];
    }
}
