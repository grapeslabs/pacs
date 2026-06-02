<?php

namespace App\MoonShine\Resources;

use App\Models\CarPassageEvent;
use App\Models\Stream;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\SelectField;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class CarEventResource extends BaseModelResource
{
    protected string $model = CarPassageEvent::class;
    protected string $title = 'Отчеты автомобилей';
    protected string $column = 'id';
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

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with(['car']);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Date::make('Дата и время', 'recognized_at')
                ->withTime()
                ->sortable(),

            Text::make('Камера', 'camera_id')
                ->changeFill(fn($item) => $this->streamNameByUid($item->camera_id)),

            Text::make('Номер', 'plate_text'),

            ColoredSelectField::make('Статус', 'car_id', fn($item) => match (true) {
                empty($item->plate_text) => 'not_recognized',
                $item->car_id !== null   => 'in_db',
                default                  => 'not_in_db',
            })->options([
                'in_db'          => ['label' => 'В базе',       'color' => 'green'],
                'not_in_db'      => ['label' => 'Не в базе',    'color' => 'yellow'],
                'not_recognized' => ['label' => 'Не распознан', 'color' => 'pink'],
            ]),

            Preview::make('Фото авто', 'image_path')
                ->changeFill(function ($item) {
                    $url = $item->imageUrl();
                    return $url ? $this->renderImageWithModal($url, 'img-' . $item->id) : '—';
                }),

            Preview::make('Фото номера', 'plate_image_path')
                ->changeFill(function ($item) {
                    $url = $item->plateImageUrl();
                    return $url ? $this->renderImageWithModal($url, 'plate-' . $item->id) : '—';
                }),
        ];
    }

    public function search(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Период отчётности', 'recognized_at')
                ->withTime(),

            SelectField::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable(),

            SelectField::make('Статус', 'db_status_filter')
                ->options([
                    'in_db'          => 'В базе',
                    'not_in_db'      => 'Не в базе',
                    'not_recognized' => 'Не распознан',
                ])
                ->placeholder('Статус')
                ->nullable()
                ->onApply(function (\Illuminate\Database\Eloquent\Builder $query, $value) {
                    return match ($value) {
                        'in_db'          => $query->whereNotNull('car_id'),
                        'not_in_db'      => $query->whereNull('car_id')->whereNotNull('plate_text')->where('plate_text', '!=', ''),
                        'not_recognized' => $query->where(fn($q) => $q->whereNull('plate_text')->orWhere('plate_text', '')),
                        default          => $query,
                    };
                }),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата и время', 'recognized_at')
                ->format('d.m.Y H:i:s'),

            Text::make('Камера', 'camera_id')
                ->changeFill(fn($item) => $this->streamNameByUid($item->camera_id)),

            Text::make('Номер', 'plate_text'),

            Text::make('Статус', 'car_id')
                ->changeFill(fn($item) => match (true) {
                    empty($item->plate_text) => 'Не распознан',
                    $item->car_id !== null   => 'В базе',
                    default                  => 'Не в базе',
                }),

        ];
    }

    private array $streamCache = [];

    private function streamNameByUid(?string $uid): string
    {
        if (empty($uid)) {
            return '—';
        }

        if (! array_key_exists($uid, $this->streamCache)) {
            $this->streamCache[$uid] = Stream::withTrashed()->where('uid', $uid)->value('name');
        }

        return $this->streamCache[$uid] ?? $uid;
    }

    private function renderImageWithModal(string $src, string $id): string
    {
        return <<<HTML
<div x-data="{ open: false, loaded: false, error: false }" x-init="
    const img = new Image();
    img.onload = () => loaded = true;
    img.onerror = () => error = true;
    img.src = '$src';
">
    <template x-if="!error && loaded">
        <div>
            <img
                src="$src"
                style="max-width: 150px; max-height: 80px; border-radius: 4px; cursor: pointer; object-fit: cover;"
                @click="open = true"
                title="Нажмите для увеличения"
                loading="lazy"
            />

            <div x-show="open" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 x-transition.opacity @click.self="open = false" @keydown.escape.window="open = false" x-cloak>
                <div style="position: relative;">
                    <img src="$src" style="max-width: 90vw; max-height: 90vh; border-radius: 8px;">
                    <button @click="open = false" style="position: absolute; top: -10px; right: -10px; background: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 20px;">×</button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="error">
        <div style="width: 150px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border: 1px dashed #dee2e6; border-radius: 4px;">
            <span style="color: #dc3545; font-size: 12px;">Ошибка загрузки</span>
        </div>
    </template>

    <template x-if="!error && !loaded">
        <div style="width: 150px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 4px;">
            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>
    </template>
</div>
HTML;
    }
}
