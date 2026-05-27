<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Fields\SelectField;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Image;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;

class EventReportResource extends BaseModelResource
{
    protected string $model = VideoAnalyticReport::class;
    protected string $title = 'Отчеты по событиям';
    protected string $column = 'name';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::DESC;

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::UPDATE,
            Action::CREATE,
            Action::VIEW,
            Action::DELETE,
            Action::MASS_DELETE
        );
    }

    public function query(): Builder
    {
        return parent::query()->with(['stream', 'person']);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),

            BelongsTo::make('Камера','stream',fn($item)=>$item->name??'Камера удалена', VideoStreamResource::class)
                ->sortable(),

            Text::make('Персона', 'person_photobank_id', fn ($item) =>
                $item->person?->getFullName() ?? $item->person_photobank_id
            ),

            Text::make('Тип распознавания', 'is_unknown', fn ($item) =>
            $item->is_unknown ? 'Неизвестное лицо' : 'Известное лицо'
            ),

            Image::make('Фото', '', fn ($item) => basename($item->data['snapshot_path'] ?? ''))
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

            SelectField::make('Тип распознавания', 'is_unknown')
                ->options([
                    'true' => 'Неизвестное лицо',
                    'false' => 'Известное лицо',
                ])
                ->placeholder('Тип распознавания')
                ->nullable()
                ->onApply(function (Builder $query, $value) {
                    return $query->where('is_unknown', $value === 'true');
                }),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

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

            Text::make('Персона', 'person_photobank_id')
                ->changeFill(function ($item) {
                    if (empty($item->person_photobank_id)) {
                        $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;
                        return empty($data['unknown_uuid']) ? 'Неизвестная персона' : $data['unknown_uuid'];
                    }

                    $id = $item->person_photobank_id;
                    static $personCache = [];
                    if (!array_key_exists($id, $personCache)) {
                        $person = Person::find($id);
                        $personCache[$id] = $person ? "[$id] " . $person->getfullname() : "[$id] Данные удалены";
                    }

                    return $personCache[$id];
                }),

            Text::make('Тип распознавания', 'is_unknown')
                ->changeFill(fn($item) => $item->is_unknown ? 'Неизвестное лицо' : 'Известное лицо'),
        ];
    }
}
