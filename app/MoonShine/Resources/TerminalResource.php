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

class TerminalResource extends BaseModelResource
{
    protected string $model = SkudController::class;
    protected string $title = 'Терминалы доступа';
    protected ?string $editModalTitle = 'Редактирование терминала';
    protected string $column = 'serial_number';
    protected bool $softDelete = false;
    protected bool $detailSidebar = true;

    public function detailSidebarData(Model $item): array
    {
        $icTablet = '<svg viewBox="0 0 24 24" fill="none"><rect x="7" y="3" width="10" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M10.5 18h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
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
            'title' => 'Карточка терминала',
            'subtitle' => 'Терминал доступа',
            'hero' => [
                'title' => $item->serial_number,
                'subtitle' => $typeLabel,
                'icon' => $icTablet,
            ],
            'sections' => $sections,
        ];
    }

    /** Секция «Последние события» устройства (по контроллеру). */
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

        return $this->buildEventsSection($events, byFace: true);
    }

    private function deviceTypeLabel(?string $type): string
    {
        return [
            'pinterm' => 'PinTerm',
            'pingate' => 'PinGate',
            'z5rweb' => 'Z5R-Web',
            'ironlogic' => 'IronLogic',
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
        $allowedTypes = ['pinterm'];
        return $builder->whereiN('type', $allowedTypes);
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
