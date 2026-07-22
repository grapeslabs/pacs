<?php

namespace App\MoonShine\Resources;

use App\MoonShine\Handlers\CsvExportHandler;
use App\MoonShine\Pages\CustomIndexPage;
use App\MoonShine\Components\SafeModal;
use App\MoonShine\Traits\HasUndoNotification;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use Closure;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\ImportExport\ExportHandler;
use MoonShine\ImportExport\ImportHandler;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;

class BaseModelResource extends ModelResource implements HasImportExportContract
{
    use HasUndoNotification;
    use ImportExportConcern;
    protected bool $createInModal=true;
    protected bool $editInModal=true;
    protected bool $detailInModal=true;
    protected bool $softDelete=true;
    protected bool $stickyTable=true;
    protected bool $usePagination=true;
    protected string $safeModalName = 'universal-safe-modal';

    protected bool $detailSidebar = false;

    protected ?string $createLabel = null;

    /** Полный заголовок сайдбара редактирования, напр. «Редактирование ключа». */
    protected ?string $editModalTitle = null;

    public function createButtonLabel(): string
    {
        return $this->createLabel ?? 'Добавить';
    }

    public function editModalTitle(): string
    {
        return $this->editModalTitle ?? 'Редактирование';
    }

    public function usesDetailSidebar(): bool
    {
        return $this->detailSidebar;
    }

    public function detailSidebarData(Model $item): array
    {
        return [];
    }

    public function findItemForDetail(int|string $id): Model
    {
        /** @var class-string<Model> $class */
        $class = $this->model;

        return $class::query()->findOrFail($id);
    }

    protected function commentIcon(): string
    {
        return '<svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5h16v11H9.5L5 20.5v-4H4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 9.5h8M8 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    }

    protected function detailEditButtonHtml(Model $item, string $label = 'Редактировать'): string
    {
        $url = $this->getFormPageUrl($item->getKey(), params: [
            '_component_name' => $this->getListComponentName(),
            '_async_form' => true,
        ], fragment: 'crud-form');

        $title = $this->editModalTitle();
        $subtitle = 'ID ' . $item->getKey();

        return (string) ActionButton::make($label, $url)
            ->icon('pencil')
            ->async(selector: "#{$this->safeModalName}_content")
            ->customAttributes([
                'class' => 'ds-btn ds-btn--primary',
                '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: '{$title}', subtitle: '{$subtitle}' }); \$dispatch('modal-toggled', { id: 'detail-sidebar' })",
            ]);
    }

    public function detailEditIconButton(Model $item, ?string $title = null): string
    {
        $url = $this->getFormPageUrl($item->getKey(), params: [
            '_component_name' => $this->getListComponentName(),
            '_async_form' => true,
        ], fragment: 'crud-form');

        $title ??= $this->editModalTitle();
        $subtitle = 'ID ' . $item->getKey();

        return (string) ActionButton::make('', $url)
            ->icon('pencil')
            ->async(selector: "#{$this->safeModalName}_content")
            ->class('ds-iconbtn')
            ->customAttributes([
                '@click.prevent.stop' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: '{$title}', subtitle: '{$subtitle}' })",
            ]);
    }

    protected function buildEventsSection(\Illuminate\Support\Collection $events, bool $byFace = false): array
    {
        $icOk = @file_get_contents(public_path('icons/accept.svg'))
            ?: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="m8.5 12.5 2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $icNo = @file_get_contents(public_path('icons/deny.svg'))
            ?: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M9 9l6 6M15 9l-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        $icSys = '<svg viewBox="0 0 24 24" fill="none"><path d="M4 12a8 8 0 0 1 13.5-5.5L20 8M20 4v4h-4M20 12a8 8 0 0 1-13.5 5.5L4 16M4 20v-4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $icPulse = '<svg viewBox="0 0 24 24" fill="none"><path d="M3 12h4l2 6 4-14 2 8h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $granted = [0, 1, 4, 5, 8, 9, 16, 17];
        $denied = [2, 3, 6, 7, 10, 11, 12, 13];
        $byLabel = $byFace ? 'по лицу' : 'по карте';

        $items = $events->map(function ($e) use ($icOk, $icNo, $icSys, $granted, $denied, $byLabel) {
            $ev = is_array($e->event) ? $e->event : (json_decode($e->event ?? '{}', true) ?: []);
            $card = $ev['card_number'] ?? null;

            $person = null;
            if ($card) {
                $person = \App\Models\Person::query()->where('key_uid', $card)->first();
                if (! $person && str_starts_with((string) $card, 'person_')) {
                    $person = \App\Models\Person::query()->find((int) substr((string) $card, 7));
                }
            }

            $code = (int) $e->type;
            if (in_array($code, $granted, true)) {
                $tone = 'ok';
                $icon = $icOk;
                $title = 'Доступ разрешён' . ($person ? ' · ' . $person->getFullName() : '');
                $subtitle = $byLabel;
            } elseif (in_array($code, $denied, true)) {
                $tone = 'danger';
                $icon = $icNo;
                $title = 'Доступ запрещён' . ($person ? ' · ' . $person->getFullName() : ' · неизвестный ключ');
                $subtitle = $byLabel;
            } else {
                $tone = 'info';
                $icon = $icSys;
                $title = $ev['event'] ?? 'Системное событие';
                $subtitle = null;
            }

            return [
                'icon' => $icon,
                'iconTone' => $tone,
                'title' => $title,
                'subtitle' => $subtitle,
                'meta' => $e->datetime ? \Illuminate\Support\Carbon::parse($e->datetime)->format('H:i:s') : null,
            ];
        })->all();

        return [
            'icon' => $icPulse,
            'title' => 'Последние события · ' . $events->count(),
            'collapsible' => true,
            'collapsed' => true,
            'itemsTight' => true,
            'items' => $items,
        ];
    }

    /** URL списка ресурса с применённым фильтром (для переходов из карточки). */
    public function filteredIndexUrl(self $resource, array $filters): string
    {
        $url = $resource->getIndexPageUrl();
        $query = http_build_query(['filter' => $filters]);

        return $url . (str_contains($url, '?') ? '&' : '?') . $query;
    }

    public function detailChevronTo(self $resource, int|string $id): string
    {
        return (string) ActionButton::make(
            '',
            route('detail-card', ['resource' => $resource->getUriKey(), 'id' => $id])
        )
            ->icon('chevron-right')
            ->async(selector: '#detail-sidebar_content')
            ->class('ds-iconbtn')
            ->customAttributes([
                '@click' => "window.dispatchEvent(new CustomEvent('ds-push'))",
            ]);
    }

    protected function indexButtons(): ListOf
    {
        $buttons = parent::indexButtons();
        if (! $this->isEditInModal()) {
            return $buttons;
        }
        return (new ListOf(ActionButton::class, [
            $this->detailSidebar ? $this->getDetailSidebarButton() : $this->getDetailButton(),
            $this->getSafeEditButton(),
            $this->getDeleteButton(),
            $this->getMassDeleteButton()
        ]));
    }

    protected function getDetailSidebarButton(): ActionButtonContract
    {
        return ActionButton::make(
            '',
            fn($item) => route('detail-card', ['resource' => $this->getUriKey(), 'id' => $item->getKey()])
        )
            ->class('js-detail-button')
            ->customAttributes([
                'style' => 'display: none !important;',
                '@click.prevent.stop' => "\$dispatch('modal-toggled', { id: 'detail-sidebar' })",
            ])
            ->async(selector: '#detail-sidebar_content')
            ->canSee(fn() => $this->can(Ability::VIEW_ANY));
    }

    protected function getSafeEditButton(): ActionButton
    {
        return ActionButton::make(
            '',
            fn($item) => $this->getFormPageUrl($item->getKey(), params: [
                '_component_name' => $this->getListComponentName(),
                '_async_form' => true,
            ], fragment: 'crud-form')
        )
            ->icon('pencil')
            ->class('js-edit-button')
            ->customAttributes([
                '@click.prevent.stop' => "let _m=(\$el.getAttribute('href')||'').match(/resourceItem=([^&]+)/); \$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: '{$this->editModalTitle()}', subtitle: _m ? ('ID '+decodeURIComponent(_m[1])) : '' })",
            ])
            ->async(selector: "#{$this->safeModalName}_content")
            ->canSee(fn() => $this->hasAction(Action::CREATE) && $this->can(Ability::CREATE));

    }

    public function getSearch(): array
    {
        return $this->search();
    }

    public function modifyFormComponent(ComponentContract $component): ComponentContract
    {
        if ($component instanceof FormBuilderContract) {
            $component->submit(null, ['style' => 'width: 136px']);
        }
        return $component;
    }

    public function modifyListComponent(ComponentContract $component): ComponentContract
    {
        if ($component instanceof TableBuilder) {
            $component->customView('components.table.builder');
        }
        return $component;
    }
    public function getQueryParamsKeys(): array
    {
        return [...parent::getQueryParamsKeys(), 'per_page'];
    }

    protected function getItemsPerPage(): int
    {
        $perPage = (int) $this->getQueryParams()->get('per_page', 0);
        if ($perPage > 0 && in_array($perPage, [10, 25, 50, 100])) {
            return $perPage;
        }
        return $this->itemsPerPage;
    }

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];

    }

    protected function hasDetailView(): bool
    {
        if (! $this->hasAction(Action::VIEW)) {
            return false;
        }
        $method = new \ReflectionMethod(static::class, 'detailFields');
        return $method->getDeclaringClass()->getName() === static::class;
    }

    protected function modifyDetailButton(ActionButtonContract $button): ActionButtonContract
    {
        if (! $this->hasDetailView()) {
            return $button->canSee(fn() => false);
        }
        return $button->customAttributes(['style' => 'display: none !important;']);
    }

    public function trAttributes(): ?Closure
    {
        if (! $this->detailSidebar && ! $this->hasDetailView()) {
            return null;
        }
        return function (mixed $item, int $index): array {
            return [
                '@click' => "if(\$event.target.closest('a,button,input,label')) return; "
                    . "document.querySelectorAll('tr.ds-row-active').forEach(el => el.classList.remove('ds-row-active')); "
                    . "\$event.currentTarget.classList.add('ds-row-active'); "
                    . "\$event.currentTarget.querySelector('.js-detail-button')?.click();",
                'style' => 'cursor: pointer;',
            ];
        };
    }

    public function getDeleteButton(
        ?string $componentName = null,
        ?string $redirectAfterDelete = null,
        bool $isAsync = true,
        string $modalName = 'resource-delete-modal',
    ): ActionButtonContract {
        return ActionButton::make(
            '',
            url: fn($item) => $this->getRoute('crud.destroy', $item->getKey())
        )
            ->name('resource-delete-button')
            ->withoutLoading()
            ->customAttributes([
                '@click.prevent.stop' => "window.dispatchEvent(new CustomEvent('open-custom-delete-modal', {detail: {url: \$el.href}}));",
            ])
            ->canSee(
                fn($item) => $item->getKey()
                    && $this->hasAction(Action::DELETE)
                    && $this->setItem($item)->can(Ability::DELETE)
            )
            ->error()
            ->icon('trash')
            ->showInLine();
    }

    /**
     * Modal-less кнопка массового удаления.
     * Подтверждение делает кастомная модалка в шаблоне таблицы,
     * поэтому вендорный withConfirm (со своей модалкой) здесь убран —
     * это исключает мелькание стандартного окна подтверждения.
     * ids собираются вендорным actions() в href через ->bulk().
     */
    public function getMassDeleteButton(
        ?string $componentName = null,
        ?string $redirectAfterDelete = null,
        bool $isAsync = true,
        string $modalName = 'resource-mass-delete-modal',
    ): ActionButtonContract {
        $componentName ??= $this->getListComponentName();

        return ActionButton::make(
            '',
            url: fn(): string => $this->getRoute('crud.massDelete')
        )
            ->name('mass-delete-button')
            ->bulk($componentName)
            ->async(
                method: HttpMethod::DELETE,
                events: [
                    $this->getListEventName($componentName, array_filter([
                        'page' => request()->getScalar('page'),
                        'sort' => request()->getScalar('sort'),
                    ])),
                ],
            )
            ->canSee(
                fn(): bool => $this->hasAction(Action::MASS_DELETE) && $this->can(Ability::MASS_DELETE)
            )
            ->error()
            ->icon('trash')
            ->showInLine();
    }

    protected function export()
    {
        return null;
    }

    protected function import()
    {
        return null;
    }

    protected function handlers(): ListOf
    {
        if (empty($this->exportFields())) {
            return parent::handlers();
        }

        $csvHandler = CsvExportHandler::make('Экспорт CSV')
            ->csv()
            ->filename('Экспорт ' . $this->getTitle())
            ->setResource($this);

        $excelHandler = ExportHandler::make('Экспорт Excel')
            ->filename('Экспорт ' . $this->getTitle())
            ->setResource($this);

        $csvHandler->modifyButton(function (ActionButtonContract $button) {
            return $button->customView('null');
        });

        $excelHandler->modifyButton(function (ActionButtonContract $button) use ($csvHandler) {
            return $button
                ->customView('components.export-button')
                ->customAttributes([
                    'data-csv-url' => $csvHandler->getUrl(),
                    'data-excel-url' => $button->getUrl(),
                ]);
        });

        return parent::handlers()
            ->add($excelHandler)
            ->add($csvHandler);
    }
    protected function afterDeleted(mixed $item): mixed
    {
        if($this->softDelete) {
            if ($item instanceof Model) {
                $this->notifyDeletedWithUndo($item);
            }
        }
        return $item;
    }
}
