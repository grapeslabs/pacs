<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Laravel\Buttons\FiltersButton;
use MoonShine\Laravel\Components\Layout\Search;
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

        return [
            Flex::make([
                Flex::make([
                    Heading::make($this->getTitle())
                        ->class('truncate text-md font-medium pl-20')->style("padding-top: 10px"),
                    ActionGroup::make()
                        ->when(
                            $resource instanceof VideoStreamResource,
                            fn (ActionGroup $group): ActionGroup => $group->add(
                                ActionButton::make(
                                    'Сетка потоков',
                                    app(VideoStreamResource::class)->getPageUrl('streams')
                                )->primary()
                            )
                        ),
                ]),

                Flex::make([
                    Search::make(placeholder: $this->buildSearchPlaceholder())->render(),
                    ActionGroup::make()
                        ->when(
                            $resource->hasFilters(),
                            fn (ActionGroup $group): ActionGroup => $group->addMany([
                                FiltersButton::for($resource)
                                    ->icon(file_get_contents(public_path('/icons/btn-filter.svg')), true),
                            ])
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
    }

    private function buildSearchPlaceholder(): string
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
                    $checkAction = function() {
                        $actions = $this->activeActions();
                        $actionsArray = is_array($actions) ? $actions : $actions->toArray();
                        return in_array(Action::CREATE, $actionsArray);
                    };
                    return $checkAction->call($resource) && $resource->can(Ability::CREATE);
                });
        }
        return ActionButton::make('Добавить', $resource->getFormPageUrl())
            ->canSee(static fn (): bool => $resource->hasAction(Action::CREATE) && $resource->can(Ability::CREATE))
            ->primary()
            ->icon('plus');

    /**
     * Возвращает кнопку импорта, если текущий ресурс — PersonResource.
     */
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

    /**
     * Возвращает кнопку экспорта, если текущий ресурс — PersonResource.
     */
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
