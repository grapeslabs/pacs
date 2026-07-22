<?php

namespace App\MoonShine\Resources;

use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\ListOf;

class ControllerResource extends BaseModelResource
{
    protected string $model = SkudController::class;
    protected string $title = 'Контроллеры СКУД';
    protected ?string $editModalTitle = 'Редактирование контроллера';
    protected string $column = 'serial_number';
    protected bool $softDelete = false;
    protected bool $detailSidebar = true;

    public function detailSidebarData(Model $item): array
    {
        $icCpu = '<svg viewBox="0 0 24 24" fill="none"><rect x="6" y="6" width="12" height="12" rx="1.6" stroke="currentColor" stroke-width="1.6"/><rect x="9.5" y="9.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><path d="M9 6V3.5M15 6V3.5M9 20.5V18M15 20.5V18M6 9H3.5M6 15H3.5M20.5 9H18M20.5 15H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
        $icGear = '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.5 5.5l2 2M16.5 16.5l2 2M18.5 5.5l-2 2M7.5 16.5l-2 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';

        $typeLabel = $this->deviceTypeLabel($item->type);

        $sections = [
            [
                'icon' => $icGear,
                'title' => 'Параметры',
                'fields' => [
                    ['label' => 'IP-адрес', 'value' => $item->ip],
                    ['label' => 'Тип', 'value' => $typeLabel],
                    ['label' => 'Добавлен', 'value' => $item->created_at?->format('d.m.Y H:i')],
                    ['label' => 'Обновлён', 'value' => $item->updated_at?->format('d.m.Y H:i')],
                ],
            ],
        ];

        $events = $this->skudEventsSection($item->getKey());
        if ($events !== null) {
            $sections[] = $events;
        }

        return [
            'title' => 'Карточка контроллера',
            'subtitle' => 'Контроллер СКУД',
            'hero' => [
                'title' => $item->serial_number,
                'subtitle' => $typeLabel,
                'icon' => $icCpu,
            ],
            'sections' => $sections,
        ];
    }

    protected function skudEventsSection(int|string $controllerId): ?array
    {
        $events = \App\Models\GrapeslabsSkudEvent::query()
            ->where('controller_id', $controllerId)
            ->latest('datetime')
            ->limit(5)
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        return $this->buildEventsSection($events);
    }

    private function deviceTypeLabel(?string $type): string
    {
        return [
            'z5rweb' => 'Z5R-Web',
            'ironlogic' => 'IronLogic',
            'pinterm' => 'PinTerm',
            'pingate' => 'PinGate',
        ][$type] ?? (string) $type;
    }

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function menuGroup(): string
    {
        return 'Оборудование';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $allowedTypes = ['z5rweb', 'ironlogic'];
        return $builder->whereIn('type', $allowedTypes);
    }
    protected function activeActions(): ListOf
    {
        return new ListOf(Action::class, [Action::VIEW, Action::DELETE, Action::MASS_DELETE]);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id')->sortable(),
            Text::make('Серийный номер', 'serial_number')->sortable(),
            Text::make('IP адрес', 'ip')->sortable(),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            Text::make('Серийный номер', 'serial_number'),
            Text::make('IP адрес', 'ip'),
        ];
    }

    public function search(): array
    {
        return ['serial_number', 'ip'];
    }

    public function filters(): iterable
    {
        return [
            Text::make('Серийный номер', 'serial_number'),
            Text::make('IP адрес', 'ip'),
        ];
    }
}
