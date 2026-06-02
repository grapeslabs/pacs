<?php

namespace App\MoonShine\Resources;

use App\Models\CarPassageEvent;
use App\Models\Passage;
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

class PassageEventResource extends BaseModelResource
{
    protected string $model = CarPassageEvent::class;
    protected string $title = 'Отчёты проезда';
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
            Action::MASS_DELETE,
        );
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder
            ->whereNotNull('passage_id')
            ->with(['passage', 'car', 'rule']);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Date::make('Дата и время', 'recognized_at')
                ->withTime()
                ->sortable(),

            Text::make('Проезд', 'passage', fn($item) => $item->passage?->name ?? '—'),

            ColoredSelectField::make('Направление', 'direction')
                ->options([
                    'entry' => ['label' => 'Въезд',  'color' => 'green'],
                    'exit'  => ['label' => 'Выезд',  'color' => 'blue'],
                ]),

            ColoredSelectField::make('Тип события', 'status')
                ->options([
                    'allowed'        => ['label' => 'Проезд разрешён', 'color' => 'green'],
                    'denied'         => ['label' => 'Проезд запрещён', 'color' => 'red'],
                    'in_db'          => ['label' => 'В базе',          'color' => 'blue'],
                    'not_in_db'      => ['label' => 'Не в базе',       'color' => 'yellow'],
                    'not_recognized' => ['label' => 'Не распознан',    'color' => 'pink'],
                ]),

            Text::make('ГРЗ', 'plate_text', fn($item) => $item->plate_text
                ? ($item->car?->license_plate ? $item->car->license_plate : $item->plate_text)
                : '—'),

            Preview::make('Фото номера', 'plate_image_path')
                ->changeFill(function ($item) {
                    $url = $item->plateImageUrl();
                    return $url ? $this->renderImageWithModal($url, 'plate-' . $item->id) : '—';
                }),

            Text::make('Правило', 'rule_name', fn($item) => $item->rule_name ?: '—'),
        ];
    }

    public function search(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Период', 'recognized_at')->withTime(),

            SelectField::make('Проезд', 'passage_id')
                ->nullable()
                ->options(Passage::query()->pluck('name', 'id')->toArray()),

            SelectField::make('Направление', 'direction')
                ->nullable()
                ->options(['entry' => 'Въезд', 'exit' => 'Выезд']),

            SelectField::make('Тип события', 'status')
                ->nullable()
                ->options(CarPassageEvent::STATUSES),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата и время', 'recognized_at')->format('d.m.Y H:i:s'),

            Text::make('Проезд', 'passage_id')
                ->changeFill(fn($item) => $item->passage?->name ?? ''),

            Text::make('Направление', 'direction')
                ->changeFill(fn($item) => match ($item->direction) {
                    'entry' => 'Въезд',
                    'exit'  => 'Выезд',
                    default => '',
                }),

            Text::make('Тип события', 'status')
                ->changeFill(fn($item) => CarPassageEvent::STATUSES[$item->status] ?? ''),

            Text::make('ГРЗ', 'plate_text'),

            Text::make('Правило', 'rule_name'),
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
            <img src="$src" style="max-width: 150px; max-height: 80px; border-radius: 4px; cursor: pointer; object-fit: cover;"
                @click="open = true" loading="lazy"/>
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
            <span style="font-size: 12px; color: #6c757d;">Загрузка...</span>
        </div>
    </template>
</div>
HTML;
    }
}
