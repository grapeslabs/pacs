<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\MoonShine\Fields\CustomText;
use App\Models\Role;
use App\MoonShine\Fields\PermissionMatrixField;
use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Contracts\Validation\Rule;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class MoonShineUserRoleResource extends BaseModelResource
{
    protected string $model = Role::class;
    protected string $title = 'Роли';
    protected string $column = 'name';
    protected bool $editInModal=false;
    protected bool $createInModal=false;
    protected bool $detailInModal=false;

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::CREATE, Action::DELETE, Action::UPDATE);
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Роль', 'name'),
            Text::make('Описание', 'description'),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            ID::make()->sortable(),
            CustomText::make(__('moonshine::ui.resource.role_name'), 'name')
                ->unique('moonshine_user_roles', 'name', 'Роль должна быть уникальной')
                ->required(),
            CustomText::make('Описание', 'description'),
            PermissionMatrixField::make('Права и доступы','permissions'),
        ];
    }

    protected function search(): array
    {
        return ['name', 'description'];
    }
}
