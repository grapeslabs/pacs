<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Permissions\Models\MoonshineUser;
use MoonShine\Permissions\Traits\WithPermissions;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Color;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Collapse;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Field;
use Stringable;

#[Icon('users')]
#[Group('moonshine::ui.resource.system', 'users', translatable: true)]
#[Order(1)]
/**
 * @extends BaseModelResource<MoonshineUser>
 */
class MoonShineUserResource extends BaseModelResource
{
    use WithPermissions;

    protected string $model = MoonshineUser::class;
    protected string $column = 'name';
    protected string $title = 'Пользователи';
    protected array $with = ['moonshineUserRole'];
    protected bool $editInModal = false;

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Роль',
                'moonshineUserRole',
                formatted: static fn (MoonshineUserRole $model) => $model->name,
                resource: MoonShineUserRoleResource::class,
            )->badge(Color::PURPLE),

            Text::make('Имя', 'name'),

            Image::make('Аватар', 'avatar')->modifyRawValue(fn (
                ?string $raw
            ): string => $raw ?? ''),

            Date::make('Дата создания', 'created_at')
                ->format("d.m.Y")
                ->sortable(),

            Email::make('E-mail', 'email')
                ->sortable(),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    protected function formFields(): iterable
    {
        return [
            Box::make([
                Tabs::make([
                    Tab::make('Основная информация', [
                        ID::make()->sortable(),

                        BelongsTo::make(
                            'Роль',
                            'moonshineUserRole',
                            formatted: static fn (MoonshineUserRole $model) => $model->name,
                            resource: MoonShineUserRoleResource::class,
                        )
                            ->creatable()
                            ->valuesQuery(static fn (Builder $q) => $q->select(['id', 'name'])),

                        Flex::make([
                            Text::make('Имя', 'name')
                                ->required(),

                            Email::make('E-mail', 'email')
                                ->required(),
                        ]),

                        Image::make('Аватар', 'avatar')
                            ->disk(moonshineConfig()->getDisk())
                            ->dir('moonshine_users')
                            ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),

                        Date::make('Дата создания', 'created_at')
                            ->format("d.m.Y")
                            ->default(now()->toDateTimeString()),
                    ])->icon('user-circle'),

                    Tab::make('Пароль', [
                        Collapse::make('Изменить пароль', [
                            Password::make('Пароль', 'password')
                                ->customAttributes(['autocomplete' => 'new-password'])
                                ->eye(),

                            PasswordRepeat::make('Повторите пароль', 'password_repeat')
                                ->customAttributes(['autocomplete' => 'confirm-password'])
                                ->eye(),
                        ])->icon('lock-closed'),
                    ])->icon('lock-closed'),
                ]),
            ]),
        ];
    }

    /**
     * @return array<string, string[]|string|list<Rule>|list<Stringable>>
     */
    protected function rules($item): array
    {
        return [
            'name' => 'required',
            'moonshine_user_role_id' => 'required',
            'email' => [
                'sometimes',
                'bail',
                'required',
                'email',
                Rule::unique('moonshine_users')->ignoreModel($item),
            ],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif'],
            'password' => $item->exists
                ? 'sometimes|nullable|min:6|required_with:password_repeat|same:password_repeat'
                : 'required|min:6|required_with:password_repeat|same:password_repeat',
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Роль',
                'moonshineUserRole',
                formatted: static fn (MoonshineUserRole $model) => $model->name,
                resource: MoonShineUserRoleResource::class,
            )->valuesQuery(static fn (Builder $q) => $q->select(['id', 'name'])),

            Text::make('E-mail', 'email')
                ->onApply(function (Builder $query, string $value, Field $field) {
                    if ($value) {
                        $query->where('email', 'ilike', '%' . $value . '%');
                    }
                    return $query;
                }),
        ];
    }
}
