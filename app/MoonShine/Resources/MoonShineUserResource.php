<?php
namespace App\MoonShine\Resources;

use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomPassword;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\PhotoField;
use App\Models\User;
use App\MoonShine\Fields\PermissionMatrixField;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Color;
use MoonShine\Support\ListOf;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Collapse;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Layout\Grid;
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
 * @extends BaseModelResource<User>
 */
class MoonShineUserResource extends BaseModelResource
{
    protected string $model = User::class;
    protected string $column = 'name';
    protected string $title = 'Пользователи';
    protected array $with = ['moonshineUserRole'];
    protected bool $createInModal=false;
    protected bool $editInModal=false;

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::CREATE, Action::DELETE, Action::UPDATE);
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Роль',
                'moonshineUserRole',
                formatted: static fn ($model) => $model->name,
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

    public function modifyFormComponent(ComponentContract $component): ComponentContract
    {
        if ($component instanceof FormBuilderContract) {
            $component->hideSubmit();
        }
        return $component;
    }

    protected function formFields(): iterable
    {
        return [
            Box::make([
                Grid::make([
                    Column::make([
                        CustomText::make('Имя', 'name')
                            ->min(2, 'Минимум 2 символа')
                            ->required(),
                    ])->columnSpan(3),
                    Column::make([
                        CustomText::make('E-mail', 'email')
                            ->email()
                            ->unique('moonshine_users', 'email','Почта должна быть уникальной')
                            ->required(),
                    ])->columnSpan(3),
                ]),
                Grid::make([
                    Column::make([
                        SelectField::make('Роль','moonshine_user_role_id')
                            ->options(MoonshineUserRole::all()->pluck('name', 'id')),
                    ])->columnSpan(3),
                    Column::make([
                        CustomDate::make('Дата создания', 'created_at')
                            ->before(Carbon::now(), 'Дата создания не может быть будущим'),
                    ])->columnSpan(3),
                ]),
                Grid::make([
                    Column::make([
                        CustomPassword::make('Пароль', 'password')
                            ->min(6, 'Пароль должен содержать не менее 6 символов')
                            ->hasUpper()
                            ->hasLower()
                            ->hasDigit()
                            ->customAttributes(['autocomplete' => 'new-password'])
                            ->eye(),
                    ])->columnSpan(3),
                    Column::make([
                        CustomPassword::make('Повторите пароль', 'password_repeat')
                            ->confirm('password')
                            ->customAttributes(['autocomplete' => 'confirm-password'])
                            ->onApply(fn($query, $value, $field) => $query)
                            ->eye(),
                    ])->columnSpan(3),
                ]),
                Grid::make([
                    Column::make([
                        Image::make('Аватар', 'avatar')
                            ->disk(moonshineConfig()->getDisk())
                            ->dir('moonshine_users')
                            ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),

                    ])->columnSpan(6)
                ]),
                PermissionMatrixField::make('Права', 'permissions')
                    ->roleField('moonshine_user_role_id'),
                ActionButton::make('Сохранить')
                    ->customAttributes(['type' => 'submit', 'style' => 'width: 136px'])
                    ->primary(),
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
                formatted: static fn ($model) => $model->name,
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
