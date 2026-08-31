<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Fields\CustomPassword;
use App\MoonShine\Fields\CustomText;
use Illuminate\Support\Facades\Storage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\Traits\WithComponentsPusher;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\FormMethod;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Grid;

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
        return 'Мой профиль';
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
            $this->avatarCard(),
            $this->personalCard(),
            $this->passwordCard(),
            ...$this->getPushedComponents(),
        ];
    }

    private function avatarCard(): ComponentContract
    {
        $user = MoonShineAuth::getGuard()->user();

        $avatarField = moonshineConfig()->getUserField('avatar') ?: 'avatar';
        $avatar = (string) ($user?->{$avatarField} ?? '');
        $placeholder = moonshineAssets()->getAsset('vendor/moonshine/avatar.jpg');

        $avatarUrl = $placeholder;
        if ($avatar !== '') {
            $avatarUrl = str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')
                ? $avatar
                : rescue(
                    fn (): string => Storage::disk(moonshineConfig()->getDisk())->url($avatar),
                    $placeholder,
                    report: false,
                );
        }

        return Box::make([
            $this->sectionHeader('Аватар'),
            Div::make()->customView('components.profile-avatar', [
                'avatarUrl' => $avatarUrl,
                'hasAvatar' => $avatar !== '',
                'uploadUrl' => route('profile-settings.avatar'),
                'deleteUrl' => route('profile-settings.avatar-delete'),
            ]),
        ])->class('profile-card');
    }

    private function personalCard(): ComponentContract
    {
        $user = MoonShineAuth::getGuard()->user();

        return Box::make([
            $this->sectionHeader('Личные данные'),
            FormBuilder::make(route('profile-settings.personal'), FormMethod::POST)
                ->fields([
                    Grid::make([
                        Column::make([
                            CustomText::make('Имя', 'name')->required(),
                        ])->columnSpan(6),
                        Column::make([
                            CustomText::make('Логин', 'username')->required(),
                        ])->columnSpan(6),
                    ]),
                ])
                ->fill([
                    'name' => (string) ($user?->{moonshineConfig()->getUserField('name') ?: 'name'} ?? ''),
                    'username' => (string) ($user?->{moonshineConfig()->getUserField('username') ?: 'email'} ?? ''),
                ])
                ->submit('Сохранить', ['class' => 'btn-primary profile-submit profile-submit--check']),
        ])->class('profile-card');
    }

    private function passwordCard(): ComponentContract
    {
        return Box::make([
            $this->sectionHeader(
                'Смена пароля',
                'Минимум 6 символов. После смены потребуется повторный вход на других устройствах.',
            ),
            FormBuilder::make(route('profile-settings.password'), FormMethod::POST)
                ->fields([
                    CustomPassword::make('Текущий пароль', 'current_password')
                        ->required()
                        ->customAttributes(['autocomplete' => 'current-password'])
                        ->eye(),
                    Grid::make([
                        Column::make([
                            CustomPassword::make('Новый пароль', 'password')
                                ->required()
                                ->min(6, 'Пароль должен содержать не менее 6 символов')
                                ->customAttributes(['autocomplete' => 'new-password'])
                                ->eye(),
                        ])->columnSpan(6),
                        Column::make([
                            CustomPassword::make('Повторите пароль', 'password_confirmation')
                                ->required()
                                ->confirm('password')
                                ->customAttributes(['autocomplete' => 'new-password'])
                                ->eye(),
                        ])->columnSpan(6),
                    ]),
                ])
                ->submit('Изменить пароль', ['class' => 'btn-primary profile-submit profile-submit--shield']),
        ])->class('profile-card');
    }

    private function sectionHeader(string $title, ?string $subtitle = null): Div
    {
        return Div::make()->customView('components.profile-section-header', [
            'title'    => $title,
            'subtitle' => $subtitle,
        ]);
    }
}
