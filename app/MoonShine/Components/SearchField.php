<?php

namespace App\MoonShine\Components;

use MoonShine\Support\Enums\FormMethod;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Text;

class SearchField extends MoonShineComponent
{
    protected string $view = 'components.search-field';

    public function __construct(
        private string $placeholder = '',
    ) {
        parent::__construct();

        if ($this->placeholder === '') {
            $this->placeholder = __('moonshine::ui.search') . ' (Ctrl+K)';
        }
    }

    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }

    protected function viewData(): array
    {
        $resource = moonshineRequest()->getResource();
        $action = $resource?->getUrl() ?? '';
        $value = moonshine()->getRequest()->getScalar('search', '');

        $form = (string) FormBuilder::make($action, FormMethod::GET)
            ->customAttributes(['x-ref' => 'searchForm'])
            ->rawMode()
            ->class('search-form')
            ->fields([
                Div::make([
                    ActionButton::make('')
                        ->rawMode()
                        ->class('ms-search-submit')
                        ->customAttributes(['type' => 'submit', 'aria-label' => 'Искать'])
                        ->icon('magnifying-glass'),

                    Text::make($this->placeholder, 'search')
                        ->setAttribute('type', 'search')
                        ->xModel('searchValue')
                        ->class('ms-search-input')
                        ->placeholder($this->placeholder)
                        ->withoutWrapper()
                        ->customAttributes([
                            'x-ref' => 'searchInput',
                            '@keyup.ctrl.k.window' => '$refs.searchInput.focus()',
                            '@input.debounce.300ms' => '$refs.searchInput.value == "" ? $refs.searchForm.submit(): ""',
                        ]),

                    ActionButton::make('')
                        ->rawMode()
                        ->onClick(fn (): string => 'searchValue = ""; $refs.searchInput.value = ""; $refs.searchForm.submit()')
                        ->class('ms-search-clear')
                        ->xShow('searchValue', '!=', '')
                        ->customAttributes(['type' => 'button', 'aria-label' => 'Очистить'])
                        ->icon('x-mark'),
                ])
                    ->customAttributes(['class' => 'ms-search-group'])
                    ->xData(['searchValue' => $value]),
            ])
            ->hideSubmit();

        return [
            'form' => $form,
        ];
    }
}
