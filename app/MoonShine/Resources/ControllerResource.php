<?php

namespace App\MoonShine\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Fields\AvatarField;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\ListOf;

class ControllerResource extends BaseModelResource
{
    protected string $model = SkudController::class;
    protected string $title = 'Контроллеры СКУД';

    public function indexSubtitle(): ?string
    {
        return $this->countSubtitle('устройство', 'устройства', 'устройств');
    }
    protected ?string $editModalTitle = 'Редактирование контроллера';
    protected string $column = 'serial_number';
    protected bool $softDelete = false;
    protected bool $detailSidebar = true;

    public function detailSidebarData(Model $item): array
    {
        $icCpu = $this->iconFile('controller');
        $icGear = $this->iconFile('gear');

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

    public function deleteConfirmation(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'title' => 'Удалить устройство?',
            'text'  => '«' . ($item->serial_number ?: 'Устройство') . '» будет удалено из системы.',
        ];
    }

    public function indexFields(): iterable
    {
        return [
            AvatarField::make('Тип', 'type')
                ->icon(fn($item) => $this->deviceTypeIconSvg($item->type))
                ->text(fn($item) => $this->controllerTypeLabel($item->type) ?: 'Контроллер')
                ->subtext(fn($item) => $item->serial_number)
                ->sortable('serial_number'),
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
