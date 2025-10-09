<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Image;

class EventReportResource extends BaseModelResource
{
    protected string $model = VideoAnalyticReport::class;
    protected string $title = 'Отчеты по событиям';
    protected string $column = 'name';
    protected string $sortColumn='id';
    protected SortDirection $sortDirection = SortDirection::DESC;

    protected function activeActions(): ListOf
    {
        return  parent::activeActions()->except(Action::UPDATE, Action::CREATE, Action::VIEW, Action::DELETE, Action::MASS_DELETE);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),
            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),
            Text::make('Камера', '', fn ($item) => Stream::where('uid', $item->camera_id)->first()->name??'Неизвестная камера')
                ->sortable(),
            Text::make('Персона', 'person_photobank_id', function($item) {
                if (empty($item->person_photobank_id)) {
                    if(empty($item->data['unknown_uuid'])) {
                        return 'Неизвестная персона';
                    }
                    return $item->data['unknown_uuid'];
                }
                return "[$item->person_photobank_id] " . Person::find($item->person_photobank_id)?->getfullname();
            })->sortable(),
            Text::make('Тип распознавания', 'is_unknown', fn($item) => $item->is_unknown ? 'Неизвестное лицо' : 'Известное лицо')
                ->sortable(),
            Image::make('Фото', '', fn($item)=>basename($item->data['snapshot_path']))
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
            DateRange::make('Период отчётности',  'datetime')
                ->withTime(),
            Select::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable()
                ->searchable(),
            Select::make('Тип распознавания', 'is_unknown')
                ->options([
                    'true' => 'Неизвестное лицо',
                    'false' => 'Известное лицо',
                ])
                ->placeholder('Тип распознавания')
                ->nullable()
                ->searchable()
                ->onApply(function (Builder $query, $value) {
                    return $query->where('is_unknown', $value === 'true');
                }),
        ];
    }

}
