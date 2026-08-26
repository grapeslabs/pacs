<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\IconTextField;
use App\MoonShine\Fields\ColoredSelectField;
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

    public function indexSubtitle(): ?string
    {
        return 'Все срабатывания распознавания лиц по камерам';
    }
    protected string $column = 'name';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::DESC;
    protected bool $detailModal = true;
    protected ?string $periodColumn = 'datetime';

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
        $personIcon = $this->iconFile('person');

        return [
            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),

            AvatarField::make('Персона', 'person_photobank_id')
                ->text(fn($item) => $item->is_unknown
                    ? 'Неизвестное лицо'
                    : ($item->person?->getFullName() ?? $item->person_photobank_id))
                ->subtext(fn($item) => $item->is_unknown
                    ? ($item->data['unknown_uuid'] ?? $item->person_photobank_id)
                    : ($item->person?->certificate_number ? $item->person->certificate_number : null))
                ->photo(fn($item) => (! $item->is_unknown && $item->person)
                    ? ($item->person->photo[0] ?? null)
                    : null)
                ->icon(fn($item) => $item->is_unknown ? $personIcon : null),

            IconTextField::make('Камера', 'camera_id')
                ->text(fn($item) => $item->stream?->name ?? 'Камера удалена')
                ->deleted(fn($item) => $item->stream === null),

            Image::make('Фото', '', fn ($item) => basename($item->data['snapshot_path'] ?? ''))
                ->disk('analytic')
                ->dir('thumbnails')
                ->setLabel('Фото'),
        ];
    }

    public function detailModalData(\Illuminate\Database\Eloquent\Model $item): array
    {
        /** @var VideoAnalyticReport $item */
        $isUnknown = (bool) $item->is_unknown;
        $person    = $item->person;

        $snapshot = $item->data['snapshot_path'] ?? null;
        $photo    = $snapshot
            ? \Illuminate\Support\Facades\Storage::disk('analytic')->url('thumbnails/' . basename($snapshot))
            : null;

        $uuid = $item->data['unknown_uuid'] ?? $item->person_photobank_id;

        $rows = [
            ['k' => 'Дата, время', 'v' => $item->datetime ? $item->datetime->format('d.m.Y, H:i:s') : '—', 'mono' => true],
            ['k' => 'Статус',      'badge' => $isUnknown
                ? ['label' => 'Неизвестное лицо', 'tone' => 'yellow']
                : ['label' => 'Известное лицо',   'tone' => 'green']],
        ];

        if ($isUnknown) {
            $rows[] = ['k' => 'Идентификатор', 'v' => $uuid, 'mono' => true];
            $rows[] = ['k' => 'Камера', 'v' => $item->stream?->name ?? 'Камера удалена'];
        } else {
            $rows[] = ['k' => 'Персона', 'v' => $person?->getFullName() ?? $item->person_photobank_id]
                + ($person ? ['link' => $this->filteredIndexUrl(app(PersonResource::class), ['last_name' => $person->last_name])] : []);
            if ($person && $person->tags_list) {
                $rows[] = ['k' => 'Теги', 'v' => $person->tags_list];
            }
            $rows[] = ['k' => 'Камера', 'v' => $item->stream?->name ?? 'Камера удалена'];
            $org = $person?->organization?->short_name ?? $person?->organization?->full_name;
            if ($org) {
                $rows[] = ['k' => 'Организация', 'v' => $org]
                    + ['link' => $this->filteredIndexUrl(app(OrganizationResource::class), ['short_name' => $org])];
            }
        }

        return [
            'title'    => $isUnknown ? 'Неопознанное лицо' : 'Распознавание лица',
            'layout'   => 'portrait',
            'photo'    => $photo,
            'initials' => $person ? mb_substr($person->last_name ?? '', 0, 1) . mb_substr($person->first_name ?? '', 0, 1) : '',
            'rows'     => $rows,
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
                    if (blank($value)) {
                        return $query;
                    }

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
                        $person = Person::where('grapesva_uuid', $id)->first();
                        $personCache[$id] = $person ? "[$id] " . $person->getfullname() : "[$id] Данные удалены";
                    }

                    return $personCache[$id];
                }),

            Text::make('Тип распознавания', 'is_unknown')
                ->changeFill(fn($item) => $item->is_unknown ? 'Неизвестное лицо' : 'Известное лицо'),
        ];
    }
}
