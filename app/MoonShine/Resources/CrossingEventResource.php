<?php

namespace App\MoonShine\Resources;

use App\Models\CrossingEvent;
use App\Models\Stream;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\IconTextField;
use App\MoonShine\Fields\SelectField;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class CrossingEventResource extends BaseModelResource
{
    protected string $model = CrossingEvent::class;
    protected string $title = 'Отчёты пересечений';

    public function indexSubtitle(): ?string
    {
        return 'Пересечение линий';
    }

    protected string $column = 'id';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::DESC;
    protected bool $detailModal = true;
    protected ?string $periodColumn = 'occurred_at';

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

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Date::make('Дата и время', 'occurred_at')
                ->withTime()
                ->sortable(),

            IconTextField::make('Камера', 'camera_id')
                ->text(fn($item) => $this->streamNameByUid($item->camera_id) === '—'
                    ? 'Камера удалена'
                    : $this->streamNameByUid($item->camera_id))
                ->deleted(fn($item) => $this->streamNameByUid($item->camera_id) === '—'),

            ColoredSelectField::make('Событие', 'type')
                ->options([
                    CrossingEvent::TYPE_CROSS => ['label' => 'Пересечение линии', 'color' => 'purple'],
                ]),

            Preview::make('Фото', 'photo_path')
                ->changeFill(function ($item) {
                    $url = $item->imageUrl();
                    return $url ? $this->renderImageWithModal($url, 'cross-' . $item->id) : '—';
                }),
        ];
    }

    public function detailModalData(Model $item): array
    {
        /** @var CrossingEvent $item */
        $rows = [
            ['k' => 'Дата и время', 'v' => $item->occurred_at ? $item->occurred_at->format('d.m.Y, H:i:s') : '—', 'mono' => true],
            ['k' => 'Камера', 'v' => $this->streamNameByUid($item->camera_id)],
            ['k' => 'Событие', 'badge' => [
                'label' => CrossingEvent::TYPES[$item->type] ?? $item->type,
                'tone'  => 'purple',
            ]],
        ];

        return [
            'title'   => 'Событие · пересечение линии',
            'layout'  => 'gallery',
            'gallery' => [
                ['label' => 'Кадр с камеры', 'image' => $item->imageUrl()],
            ],
            'rows'    => $rows,
        ];
    }

    public function search(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Период', 'occurred_at')->withTime(),

            SelectField::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable(),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата и время', 'occurred_at')->format('d.m.Y H:i:s'),

            Text::make('Камера', 'camera_id')
                ->changeFill(fn($item) => $this->streamNameByUid($item->camera_id)),

            Text::make('Событие', 'type')
                ->changeFill(fn($item) => CrossingEvent::TYPES[$item->type] ?? ''),
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
            <img src="$src" style="max-width: 150px; max-height: 80px; border-radius: 4px; cursor: pointer; object-fit: cover;"
                @click.stop="open = true" loading="lazy"/>
            <div x-show="open" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 x-transition.opacity @click.self.stop="open = false" @keydown.escape.window="open = false" x-cloak>
                <div style="position: relative;">
                    <img src="$src" style="max-width: 90vw; max-height: 90vh; border-radius: 8px;">
                    <button @click.stop="open = false" style="position: absolute; top: -10px; right: -10px; background: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 20px;">×</button>
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
