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

    protected function indexButtons(): ListOf
    {
        $buttons = parent::indexButtons();
        if (! $this->isEditInModal()) {
            return $buttons;
        }
        return (new ListOf(ActionButton::class, [
            $this->getDetailButton(),
            $this->getSafeEditButton(),
            $this->getDeleteButton(),
            $this->getMassDeleteButton()
        ]));
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
                '@click.prevent.stop' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Редактирование' })",
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
            $component->submit(null, ['style' => 'width: 136px; margin-left: 1.5rem']);
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
        if (! $this->hasDetailView()) {
            return null;
        }
        return function (mixed $item, int $index): array {
            return [
                '@click' => "if(\$event.target.closest('a,button,input,label')) return; \$event.currentTarget.querySelector('.js-detail-button')?.click();",
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
