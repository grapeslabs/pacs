<?php

namespace App\MoonShine\Resources;

use App\MoonShine\Handlers\CsvExportHandler;
use App\MoonShine\Pages\CustomIndexPage;
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

    /** Открывать детальный просмотр записи в центрированной модалке (для отчётов). */
    protected bool $detailModal = false;

    /** Колонка даты для быстрого фильтра «Сегодня / 7 дней / 30 дней» (для отчётов). */
    protected ?string $periodColumn = null;

    private bool $periodApplied = false;

    protected ?string $createLabel = null;

    /** Полный заголовок сайдбара редактирования, напр. «Редактирование ключа». */
    protected ?string $editModalTitle = null;

    public function createButtonLabel(): string
    {
        return $this->createLabel ?? 'Добавить';
    }

    /** Подзаголовок под заголовком index-страницы (описание или счётчик). null — не показывать. */
    public function indexSubtitle(): ?string
    {
        return null;
    }

    /** Русская форма числительного: 1 ключ / 2 ключа / 5 ключей. */
    protected function pluralRu(int $n, string $one, string $few, string $many): string
    {
        $mod10  = $n % 10;
        $mod100 = $n % 100;

        $form = match (true) {
            $mod10 === 1 && $mod100 !== 11                          => $one,
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => $few,
            default                                                 => $many,
        };

        return $n . ' ' . $form;
    }

    /** Подзаголовок-счётчик по всем записям модели (без фильтров периода). */
    protected function countSubtitle(string $one, string $few, string $many): string
    {
        $count = (int) $this->getModel()->newQuery()->count();

        return $this->pluralRu($count, $one, $few, $many);
    }

    public function editModalTitle(): string
    {
        return $this->editModalTitle ?? 'Редактирование';
    }

    /**
     * Метаданные всех полей фильтра (column + label).
     * Бейджи строятся на клиенте из текущих значений формы фильтров,
     * т.к. фильтр применяется асинхронно без перезагрузки страницы.
     */
    public function filterFieldsMeta(): array
    {
        if (! $this->hasFilters()) {
            return [];
        }

        $meta = [];

        foreach ($this->filters() as $field) {
            if (! is_object($field) || ! method_exists($field, 'getColumn')) {
                continue;
            }

            $meta[] = [
                'column' => $field->getColumn(),
                'label'  => method_exists($field, 'getLabel') ? $field->getLabel() : $field->getColumn(),
            ];
        }

        return $meta;
    }

    /**
     * Активные фильтры для отрисовки бейджей под тулбаром.
     * Возвращает список ['label', 'value', 'column'].
     */
    public function activeFilterBadges(): array
    {
        if (! $this->hasFilters()) {
            return [];
        }

        $params = collect($this->getFilterParams());
        $badges = [];

        foreach ($this->filters() as $field) {
            if (! is_object($field) || ! method_exists($field, 'getColumn')) {
                continue;
            }

            $column = $field->getColumn();
            $value = $params->get($column);

            if (blank($value) || $value === '0') {
                continue;
            }

            $display = is_array($value) ? implode(', ', array_filter($value, fn ($v) => ! blank($v))) : (string) $value;

            if ($display === '') {
                continue;
            }

            $badges[] = [
                'label'  => method_exists($field, 'getLabel') ? $field->getLabel() : $column,
                'value'  => $display,
                'column' => $column,
            ];
        }

        return $badges;
    }

    public function usesDetailSidebar(): bool
    {
        return $this->detailSidebar;
    }

    public function detailSidebarData(Model $item): array
    {
        return [];
    }

    public function usesDetailModal(): bool
    {
        return $this->detailModal;
    }

    /** Payload для модалки детального просмотра записи отчёта (см. components/detail/modal.blade.php). */
    public function detailModalData(Model $item): array
    {
        return [];
    }

    /** SVG-иконка устройства СКУД по типу контроллера (терминал/шлагбаум/контроллер). */
    protected function deviceTypeIconSvg(?string $type): string
    {
        $file = match ($type) {
            'pinterm' => 'terminal.svg',
            'pingate' => 'gate.svg',
            default   => 'controller.svg', // z5rweb, ironlogic и прочие контроллеры
        };

        // Фолбэк — инлайн-иконка «устройство», если svg-файл ещё не задеплоен,
        // чтобы в аватаре оборудования не появлялась буква-инициал.
        return @file_get_contents(public_path('icons/' . $file))
            ?: $this->deviceFallbackIconSvg();
    }

    /** Инлайн-иконка-заглушка устройства (когда файла иконки нет). */
    protected function deviceFallbackIconSvg(): string
    {
        return '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h8M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    }

    /** Человекочитаемый лейбл типа контроллера СКУД. */
    protected function controllerTypeLabel(?string $type): string
    {
        return [
            'pinterm'   => 'PinTerm',
            'pingate'   => 'PinGate',
            'z5rweb'    => 'Z5R-Web',
            'ironlogic' => 'IronLogic',
        ][$type] ?? (string) $type;
    }

    /** Доступен ли быстрый фильтр периода (сегмент «Сегодня / 7 дней / …»). */
    public function hasPeriodFilter(): bool
    {
        return $this->periodColumn !== null;
    }

    /** Текущий выбранный период (из query-параметра). */
    public function currentPeriod(): string
    {
        $period = (string) request()->query('period', '');

        return in_array($period, ['today', '7d', '30d', 'custom'], true) ? $period : '';
    }

    /** Опции сегмента периода: [key, label]. */
    public function periodOptions(): array
    {
        return [
            ['key' => 'today', 'label' => 'Сегодня'],
            ['key' => '7d',    'label' => '7 дней'],
            ['key' => '30d',   'label' => '30 дней'],
            ['key' => 'custom','label' => 'Период'],
        ];
    }

    protected function applyPeriodFilter(\Illuminate\Contracts\Database\Eloquent\Builder $builder): \Illuminate\Contracts\Database\Eloquent\Builder
    {
        if ($this->periodColumn === null) {
            return $builder;
        }

        $now = \Illuminate\Support\Carbon::now();
        [$from, $to] = match ($this->currentPeriod()) {
            'today'  => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d'     => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d'    => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => $this->customPeriodRange(),
            default  => [null, null],
        };

        if ($from !== null && $to !== null) {
            $builder->whereBetween($this->periodColumn, [$from, $to]);
        } elseif ($from !== null) {
            $builder->where($this->periodColumn, '>=', $from);
        } elseif ($to !== null) {
            $builder->where($this->periodColumn, '<=', $to);
        }

        return $builder;
    }

    /** Диапазон для произвольного периода из query-параметров from/to. */
    protected function customPeriodRange(): array
    {
        $parse = function (?string $value): ?\Illuminate\Support\Carbon {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }
            try {
                return \Illuminate\Support\Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        };

        $from = $parse(request()->query('from'));
        $to   = $parse(request()->query('to'));

        return [
            $from?->startOfDay(),
            $to?->endOfDay(),
        ];
    }

    public function newQuery(): \Illuminate\Contracts\Database\Eloquent\Builder
    {
        $builder = parent::newQuery();

        if (! $this->periodApplied) {
            $this->periodApplied = true;
            $this->applyPeriodFilter($builder);
        }

        return $builder;
    }

    /**
     * Текст модалки подтверждения удаления записи: ['title' => ..., 'text' => ...].
     * Дефолт — «ближайший аналог»; каждый ресурс переопределяет своим текстом.
     */
    public function deleteConfirmation(Model $item): array
    {
        return [
            'title' => 'Удалить запись?',
            'text'  => 'Запись будет удалена безвозвратно.',
        ];
    }

    public function findItemForDetail(int|string $id): Model
    {
        /** @var class-string<Model> $class */
        $class = $this->model;

        return $class::query()->findOrFail($id);
    }

    protected function iconFile(string $name): string
    {
        static $cache = [];

        return $cache[$name] ??= (string) @file_get_contents(public_path("icons/{$name}.svg"));
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
        $icOk = $this->iconFile('accept');
        $icNo = $this->iconFile('deny');
        $icSys = $this->iconFile('sync');
        $icPulse = $this->iconFile('pulse');

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
            match (true) {
                $this->detailModal   => $this->getDetailModalButton(),
                $this->detailSidebar => $this->getDetailSidebarButton(),
                default              => $this->getDetailButton(),
            },
            $this->getSafeEditButton(),
            $this->getDeleteButton(),
            $this->getMassDeleteButton()
        ]));
    }

    protected function getDetailModalButton(): ActionButtonContract
    {
        return ActionButton::make(
            '',
            fn($item) => route('detail-modal', ['resource' => $this->getUriKey(), 'id' => $item->getKey()])
        )
            ->class('js-detail-button')
            ->withoutLoading()
            ->customAttributes([
                'style' => 'display: none !important;',
                '@click.prevent.stop' => "\$dispatch('modal-toggled', { id: 'detail-modal' })",
            ])
            ->async(selector: '#detail-modal_content')
            ->canSee(fn() => $this->can(Ability::VIEW_ANY));
    }

    protected function getDetailSidebarButton(): ActionButtonContract
    {
        return ActionButton::make(
            '',
            fn($item) => route('detail-card', ['resource' => $this->getUriKey(), 'id' => $item->getKey()])
        )
            ->class('js-detail-button')
            ->withoutLoading()
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
            ->icon($this->iconFile('pencil'), true)
            ->class(['js-edit-button', 'ds-rowbtn'])
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
            $component->submit(null, ['class' => 'js-form-sidebar-submit']);
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
        $hasDetail = $this->detailSidebar || $this->detailModal || $this->hasDetailView();

        // Если детальной панели нет — клик по строке открывает редактирование в safe-modal.
        if (! $hasDetail && ! $this->isEditInModal()) {
            return null;
        }

        $clickTarget = $hasDetail ? '.js-detail-button' : '.js-edit-button';

        return function (mixed $item, int $index) use ($clickTarget): array {
            return [
                '@click' => "if(\$event.target.closest('a,button,input,label,img')) return; "
                    . "document.querySelectorAll('tr.ds-row-active').forEach(el => el.classList.remove('ds-row-active')); "
                    . "\$event.currentTarget.classList.add('ds-row-active'); "
                    . "\$event.currentTarget.querySelector('{$clickTarget}')?.click();",
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
            ->class('ds-rowbtn')
            ->withoutLoading()
            ->onAfterSet(function ($data, $ctx): void {
                $model = $data?->getOriginal();
                if (! $model instanceof Model) {
                    return;
                }
                $conf = $this->deleteConfirmation($model);
                $ctx->customAttributes([
                    'data-delete-title' => $conf['title'] ?? 'Удалить запись?',
                    'data-delete-text'  => $conf['text'] ?? '',
                ]);
            })
            ->customAttributes([
                '@click.prevent.stop' => "window.dispatchEvent(new CustomEvent('open-custom-delete-modal', {detail: {url: \$el.href, title: \$el.dataset.deleteTitle, text: \$el.dataset.deleteText}}));",
            ])
            ->canSee(
                fn($item) => $item->getKey()
                    && $this->hasAction(Action::DELETE)
                    && $this->setItem($item)->can(Ability::DELETE)
            )
            ->icon($this->iconFile('trashbin'), true)
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
