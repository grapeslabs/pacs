<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\Laravel\Buttons\FiltersButton;
use MoonShine\Laravel\Components\Layout\Search;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Flex;

class CustomIndexPage extends IndexPage
{
    protected string $safeModalName = 'universal-safe-modal';

    public function getPageButtons(): array
    {
        $resource = $this->getResource();
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

                    Search::make()->render(),

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

                ActionButton::make(
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
                    })
                ])
            ])
                ->justifyAlign('between')
                ->itemsAlign('center')
                ->class('mb-6'),
        ];
    }
}
