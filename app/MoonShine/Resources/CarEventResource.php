<?php

namespace App\MoonShine\Resources;

use App\Models\GrzReport;
use App\Models\Stream;
use App\MoonShine\Fields\SelectField;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class CarEventResource extends BaseModelResource
{
    protected string $model = GrzReport::class;
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

    public function query(): Builder
    {
        return parent::query()->with(['stream', 'car']);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Date::make('Дата и время', 'created_at')
                ->withTime()
                ->sortable(),

            BelongsTo::make('Камера', 'stream', fn($item) => $item->name ?? 'Камера удалена', VideoStreamResource::class)
                ->sortable(),

            Text::make('Номер', 'plate_text'),

            Text::make('Статус', 'is_authorized', fn($item) =>
                $item->is_authorized ? 'Авторизован' : 'Неизвестен'
            ),

            Preview::make('Фото авто', 'image')
                ->changeFill(function ($item) {
                    if (empty($item->image)) return '—';
                    $src = str_contains($item->image, 'base64,')
                        ? $item->image
                        : 'data:image/jpeg;base64,' . $item->image;
                    return $this->renderImageWithModal($src, 'img-' . $item->id);
                }),

            Preview::make('Фото номера', 'plate')
                ->changeFill(function ($item) {
                    if (empty($item->plate)) return '—';
                    $src = str_contains($item->plate, 'base64,')
                        ? $item->plate
                        : 'data:image/jpeg;base64,' . $item->plate;
                    return $this->renderImageWithModal($src, 'plate-' . $item->id);
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
            DateRange::make('Период отчётности', 'created_at')
                ->withTime(),

            SelectField::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable(),

            SelectField::make('Статус', 'is_authorized')
                ->options([
                    'true'  => 'Авторизован',
                    'false' => 'Неизвестен',
                ])
                ->placeholder('Статус')
                ->nullable()
                ->onApply(function (Builder $query, $value) {
                    return $query->where('is_authorized', $value === 'true');
                }),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата и время', 'created_at')
                ->format('d.m.Y H:i:s'),

            Text::make('Камера', 'camera_id')
                ->changeFill(function ($item) {
                    if (empty($item->camera_id)) return 'Неизвестная камера';
                    static $cache = [];
                    if (!array_key_exists($item->camera_id, $cache)) {
                        $cache[$item->camera_id] = Stream::where('uid', $item->camera_id)->first()?->name;
                    }
                    return $cache[$item->camera_id] ?? 'Неизвестная камера';
                }),

            Text::make('Номер', 'plate_text'),

            Text::make('Статус', 'is_authorized')
                ->changeFill(fn($item) => $item->is_authorized ? 'Авторизован' : 'Неизвестен'),

            Text::make('Описание', 'car_description')
                ->changeFill(function ($item) {
                    return $item->car?->comment ?? '';
                }),
        ];
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