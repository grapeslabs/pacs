<?php

declare(strict_types=1);

namespace App\MoonShine\Forms;

use Illuminate\Support\Arr;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Fields\Relationships\ModelRelationField;
use MoonShine\Laravel\Resources\CrudResource;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\FormMethod;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Hidden;
use Stringable;
use Throwable;

/**
 * @method static static make(CrudResource $resource)
 */
final readonly class FiltersForm implements FormContract
{
    use Makeable;

    public function __construct(private CrudResource $resource)
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): FormBuilderContract
    {
        $resource = $this->resource;

        $resource->setQueryParams(
            request()->only($resource->getQueryParamsKeys()),
        );

        $values = $resource->getFilterParams();
        $filters = $resource->getFilters();

        $action = $resource->isAsync() ? '#' : $this->getFormAction();

        foreach ($filters->onlyFields() as $filter) {
            if (! $filter instanceof ModelRelationField) {
                $filter->fillData($values);
                data_forget($values, $filter->getColumn());
            }
        }

        $sort = request()->getScalar('sort');
        $queryTag = request()->getScalar('query-tag');

        return FormBuilder::make($action, FormMethod::GET)
            ->name('filters')
            ->fillCast($values, $resource->getCaster())
            ->fields(
                $filters
                    ->when(
                        $sort,
                        static fn ($fields): Fields
                            => $fields
                            ->prepend(
                                Hidden::make(column: 'sort')->setValue(
                                    $sort,
                                ),
                            ),
                    )
                    ->when(
                        $queryTag,
                        static fn ($fields): Fields
                            => $fields
                            ->prepend(
                                Hidden::make(column: 'query-tag')->setValue(
                                    $queryTag,
                                ),
                            ),
                    )
                    ->toArray(),
            )
            ->when($resource->isAsync(), function (FormBuilderContract $form) use ($resource): void {
                $events = [
                    $resource->getListEventName(),
                    AlpineJs::event(JsEvent::OFF_CANVAS_TOGGLED, 'filters-off-canvas'),
                ];

                $form->customAttributes([
                    '@submit.prevent' => "asyncFilters(
                        `" . AlpineJs::prepareEvents($events) . "`,
                        `_component_name,_token,_method`
                    )",
                ]);

                $form->buttons([
                    $this->getResetButton($resource->isAsync(), true),
                ]);
            })
            ->submit(__('moonshine::ui.search'), ['class' => 'btn-primary'])
            ->when(
                $resource->getFilterParams() !== [],
                fn (FormBuilderContract $form): FormBuilderContract => $form->buttons([
                    $this->getResetButton(),
                ]),
            );
    }

    private function getResetButton(bool $async = false, bool $hide = false): ActionButton
    {
        $button = ActionButton::make(
            __('moonshine::ui.reset'),
            $this->getFormAction(query: ['reset' => true]),
        )
            ->secondary()
            ->showInLine()
            ->class('js-async-reset-button');

        if ($hide) {
            $button = $button->customAttributes([
                'style' => 'display: none',
            ]);
        }

        if (! $async) {
            return $button;
        }

        return $button
            ->dispatchEvent([
                AlpineJs::event(
                    JsEvent::FORM_RESET,
                    'filters',
                ),
                AlpineJs::event(
                    JsEvent::FORM_SUBMIT,
                    'filters',
                ),
            ], withoutPayload: true);
    }

    private function getFormAction(array $query = []): string
    {
        return str(request()->url())->when(
            $query,
            static fn (Stringable $str): Stringable
                => $str
                ->append('?')
                ->append(Arr::query($query)),
        )->value();
    }
}
