<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Components\FilterSidebar;
use App\MoonShine\Components\SearchField;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Laravel\Forms\FiltersForm;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Support\Enums\FormMethod;

class CustomIndexPage extends IndexPage
{
    protected string $safeModalName = 'universal-safe-modal';

    public function getPageButtons(): array
    {
        $resource = $this->getResource();
        $importButton = $this->getImportButtonIfNeeded($resource);
        $exportButton = $this->getExportButtonIfNeeded($resource);

        $filterSidebar = null;
        $filtersButton = null;

        if ($resource->hasFilters()) {
            $form = moonshineConfig()->getForm('filters', FiltersForm::class, resource: $resource);

            $count = collect($resource->getFilterParams())
                ->filter(fn ($value): bool => ! blank($value) && $value !== '0')
                ->count();

            $filterSidebar = FilterSidebar::make(
                subtitle: $this->getTitle(),
                form: $form,
                count: $count,
            )->name('filters-sidebar');

            $filtersLabel = 'Фильтры'
                . '<span x-data x-show="$store.filters && $store.filters.count > 0" x-cloak '
                . 'style="display:inline-flex;align-items:center;">'
                . '<span class="badge" '
                . 'style="display:inline-flex;align-items:center;justify-content:center;margin-left:0.5rem;background:#6972F0;color:#ffffff;border-radius:9999px;min-width:20px;text-align:center;padding:1px 7px;font-size:12px;font-weight:700;line-height:1.5;" '
                . 'x-text="$store.filters ? $store.filters.count : 0">' . $count . '</span>'
                . '</span>';

            $filterIconPath = public_path('/icons/btn-filter.svg');
            $hasFilterIcon = is_file($filterIconPath);
            $filterIcon = $hasFilterIcon ? file_get_contents($filterIconPath) : 'adjustments-horizontal';

            $filtersButton = ActionButton::make($filtersLabel, '#')
                ->class('js-filter-button')
                ->icon($filterIcon, $hasFilterIcon)
                ->customAttributes([
                    '@click.prevent' => "\$dispatch('modal-toggled', { id: 'filters-sidebar' })",
                    'style' => 'display: inline-flex; align-items: center; gap: 0.5rem; line-height: 1; padding: 0.625rem 1rem; border-radius: 0.5rem; background-color: #ffffff; color: #7F7F9D; border: 1px solid #EAEDFB;',
                ]);
        }

        $components = [
            Flex::make([
                Flex::make([
                    Heading::make($this->getTitle())
                        ->class('truncate text-md font-medium pl-20')->style("padding-top: 10px"),
                ]),

                Flex::make([
                    SearchField::make(placeholder: $this->buildSearchPlaceholder()),
                    ActionGroup::make()
                        ->when(
                            $filtersButton !== null,
                            fn (ActionGroup $group): ActionGroup => $group->add($filtersButton)
                        )
                        ->when(
                            $resource->getHandlers()->isNotEmpty(),
                            fn (ActionGroup $group): ActionGroup => $group->addMany(
                                $this->getResource()->getHandlers()->getButtons()
                            )
                        )
                        ->class('gap-4'),
                    $importButton,
                    $exportButton,
                    $this->getCustomCreateButton(),
                ])
            ])
                ->justifyAlign('between')
                ->itemsAlign('center')
                ->class('mb-6'),
        ];

        if ($filterSidebar !== null) {
            $components[] = $filterSidebar;
        }

        return $components;
    }

    protected function buildSearchPlaceholder(): string
    {
        $resource = $this->getResource();
        $searchColumns = method_exists($resource, 'getSearch') ? $resource->getSearch() : [];

        if (empty($searchColumns)) {
            return __('moonshine::ui.search') . ' (Ctrl+K)';
        }

        $fieldLabels = collect($resource->getIndexFields()->onlyFields())
            ->mapWithKeys(fn($field) => [$field->getColumn() => $field->getLabel()]);

        $labels = collect($searchColumns)
            ->map(fn(string $col) =>
                $fieldLabels->get($col)
                ?? $fieldLabels->get(explode('.', $col)[0])
            )
            ->filter()
            ->unique()
            ->join(', ');

        return 'Поиск: ' . $labels . ' (Ctrl+K)';
    }

    protected function getCustomCreateButton()
    {
        $resource = $this->getResource();
        if($resource->isCreateInModal()) {
            return ActionButton::make(
                'Добавить',
                fn($item) => $resource->getFormPageUrl(params: [
                    '_component_name' => $this->getListComponentName(),
                    '_async_form' => true,
                ], fragment: 'crud-form')
            )
                ->customAttributes([
                    '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Добавление' })",
                ])
                ->async(selector: "#{$this->safeModalName}_content")
                ->primary()
                ->icon('plus')
                ->canSee(function () use ($resource) {
                    $checkAction = function () {
                        $actions = $this->activeActions();
                        $actionsArray = is_array($actions) ? $actions : $actions->toArray();
                        return in_array(Action::CREATE, $actionsArray);
                    };
                    return $checkAction->call($resource) && $resource->can(Ability::CREATE);
                });
        }
        return ActionButton::make('Добавить', $resource->getFormPageUrl())
            ->canSee(static fn(): bool => $resource->hasAction(Action::CREATE) && $resource->can(Ability::CREATE))
            ->primary()
            ->icon('plus');
    }

    protected function getImportButtonIfNeeded($resource): ?ActionButton
    {
        if (!$resource instanceof PersonResource) {
            return null;
        }

        return ActionButton::make('Импорт')
            ->primary()
            ->inModal(
                title: 'Загрузка архива с фотографиями',
                content: function () {
                    return (string) FormBuilder::make(
                        route('person-photos.import'),
                        FormMethod::POST,
                    )
                        ->fields([
                            File::make('ZIP-архив', 'archive')
                                ->required()
                                ->allowedExtensions(['zip']),
                        ])
                        ->submit('Загрузить', ['class' => 'btn-primary']);
                },
                name: 'import-person-photos',
            );
    }

    protected function getExportButtonIfNeeded($resource): ?ActionButton
    {
        if (!$resource instanceof PersonResource) {
            return null;
        }

        return ActionButton::make('Экспорт', route('person-photos.export-all'))
            ->primary()
            ->blank();
    }
}
