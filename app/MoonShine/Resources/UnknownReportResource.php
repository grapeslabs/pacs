<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Handlers\Handler;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Image;

class UnknownReportResource extends BaseModelResource implements HasImportExportContract
{
    use ImportExportConcern;
    protected string $model = VideoAnalyticReport::class;
    protected string $title = 'Отчеты по неизвестным';
    protected string $column = 'name';
    protected string $sortColumn='id';
    protected SortDirection $sortDirection = SortDirection::DESC;
    protected string $oldPerson;

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where('person_photobank_id', '=', '');
    }

    protected function activeActions(): ListOf
    {
        return  parent::activeActions()->except(Action::UPDATE, Action::CREATE, Action::VIEW, Action::DELETE, Action::MASS_DELETE);
    }

    public function indexFields(): iterable
    {

        return [
            text::make('Персона', 'data->unknown_uuid', function($item) {
                if(empty($item->data['unknown_uuid'])) { return 'Неизвестная персона'; }
                if(!empty($this->oldPerson) && $this->oldPerson == $item->data['unknown_uuid']) {
                    return "";
                }
                $this->oldPerson = $item->data['unknown_uuid'];
                return $item->data['unknown_uuid'];
            })->sortable(),

            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),
            Text::make('Камера', '', fn ($item) => Stream::where('uid', $item->camera_id)->first()->name??'Неизвестная камера'),
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
        ];
    }

    protected function import():array
    {
        return [];
    }

    public function exportFields(): iterable
    {
        return [
            Text::make('Персона', 'data')
                ->changeFill(function($item) {
                    $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

                    return empty($data['unknown_uuid']) ? 'Неизвестная персона' : $data['unknown_uuid'];
                }),

            Date::make('Дата и время', 'datetime')
                ->format('d.m.Y H:i:s'),

            Text::make('Камера', 'camera_id')
                ->changeFill(function ($item) {
                    if (empty($item->camera_id)) return 'Неизвестная камера';
                    static $cameraCache = [];

                    if (!array_key_exists($item->camera_id, $cameraCache)) {
                        $cameraCache[$item->camera_id] = Stream::where('uid', $item->camera_id)->first()?->name;
                    }

                    return $cameraCache[$item->camera_id] ?? 'Неизвестная камера';
                }),

            Text::make('Имя файла фото', 'data')
                ->changeFill(function($item) {
                    $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

                    return !empty($data['snapshot_path']) ? basename($data['snapshot_path']) : '';
                }),
        ];
    }
}
