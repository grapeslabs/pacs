<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Http\Controllers\ProfileController;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Traits\WithComponentsPusher;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\ActionButton;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomPassword;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Text;

#[SkipMenu]
class ProfilePage extends Page
{
    use WithComponentsPusher;

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string
    {
        return __('moonshine::ui.profile');
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $cfg = moonshineConfig();

        $cards = [];

        // АВАТАР
        if ($cfg->getUserField('avatar')) {
            $cards[] = Box::make([
                $this->sectionHeader('Аватар'),
                Image::make(__('moonshine::ui.resource.avatar'), $cfg->getUserField('avatar'))
                    ->disk($cfg->getDisk())
                    ->options($cfg->getDiskOptions())
                    ->dir('moonshine_users')
                    ->removable()
                    ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),
            ])->class('profile-card');
        }

        // ЛИЧНЫЕ ДАННЫЕ
        $personal = [$this->sectionHeader('Личные данные')];
        $personalGrid = [];
        if ($cfg->getUserField('name')) {
            $personalGrid[] = Column::make([
                CustomText::make(__('moonshine::ui.resource.name'), $cfg->getUserField('name'))->required(),
            ])->columnSpan(6);
        }
        if ($cfg->getUserField('username')) {
            $personalGrid[] = Column::make([
                CustomText::make(__('moonshine::ui.login.username'), $cfg->getUserField('username'))->required(),
            ])->columnSpan(6);
        }
        $personal[] = Grid::make($personalGrid);
        $cards[] = Box::make($personal)->class('profile-card');

        // СМЕНА ПАРОЛЯ
        if ($cfg->getUserField('password')) {
            $cards[] = Box::make([
                $this->sectionHeader('Смена пароля', 'Минимум 6 символов. После смены потребуется повторный вход на других устройствах.'),
                Grid::make([
                    Column::make([
                        CustomPassword::make(__('moonshine::ui.resource.password'), $cfg->getUserField('password'))
                            ->min(6, 'Пароль должен содержать не менее 6 символов')
                            ->customAttributes(['autocomplete' => 'new-password'])
                            ->eye(),
                    ])->columnSpan(6),
                    Column::make([
                        CustomPassword::make(__('moonshine::ui.resource.repeat_password'), 'password_repeat')
                            ->confirm($cfg->getUserField('password'))
                            ->customAttributes(['autocomplete' => 'confirm-password'])
                            ->onApply(fn ($query, $value, $field) => $query)
                            ->eye(),
                    ])->columnSpan(6),
                ]),
            ])->class('profile-card');
        }

        return $cards;
    }

    private function sectionHeader(string $title, ?string $subtitle = null): Div
    {
        return Div::make()->customView('components.profile-section-header', [
            'title'    => $title,
            'subtitle' => $subtitle,
        ]);
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            Div::make()->customView('components.profile-header', [
                'title'    => 'Мой профиль',
                'subtitle' => 'Личные данные учётной записи и безопасность',
            ]),
            $this->getForm(),
            ...$this->getPushedComponents(),
        ];
    }

    public function getForm(): FormBuilderContract
    {
        $user = MoonShineAuth::getGuard()->user() ?? MoonShineAuth::getModel();

        return FormBuilder::make(action([ProfileController::class, 'store']))
            ->async()
            ->fields($this->fields())
            ->fillCast($user, new ModelCaster($user::class))
            ->submit(__('moonshine::ui.save'), [
                'class' => 'btn-lg btn-primary',
            ]);
    }
}
