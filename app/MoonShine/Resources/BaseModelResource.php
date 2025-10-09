<?php

namespace App\MoonShine\Resources;

use App\MoonShine\Pages\CustomIndexPage;
use App\MoonShine\Components\SafeModal;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;

class BaseModelResource extends ModelResource
{
    protected bool $createInModal=true;
    protected bool $editInModal=true;
    protected bool $detailInModal=true;

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
                '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Редактирование' })",
            ])
            ->async(selector: "#{$this->safeModalName}_content")
            ->canSee(fn() => in_array(Action::UPDATE, $this->activeActions()->toArray()));
    }

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];

    }

    protected function modifyDeleteButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->withConfirm(
            title: 'Подтверждение',
            content: 'Вы уверены, что хотите удалить данный элемент?',
            button: 'Подтвердить',
            method: HttpMethod::DELETE,
        );
    }

    protected function modifyMassDeleteButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->withConfirm(
            title: 'Подтверждение',
            content: 'Вы уверены, что хотите удалить данные элементы?',
            button: 'Подтвердить',
            method: HttpMethod::DELETE,
        );
    }


}
