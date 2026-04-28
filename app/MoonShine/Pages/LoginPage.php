<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Fields\AuthPasswordField;
use App\MoonShine\Fields\AuthUsernameField;
use App\MoonShine\Layouts\LoginLayout;
use Illuminate\Support\Facades\Cache;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Switcher;


#[SkipMenu]
#[Layout(LoginLayout::class)]
class LoginPage extends Page
{
    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            FormBuilder::make()
            ->action(moonshineRouter()->to('authenticate'))
            ->fields([
                AuthUsernameField::make('Логин', 'username')
                    ->withoutWrapper()
                    ->required()
                    ->customAttributes([
                        'autofocus' => true,
                        'autocomplete' => 'username',
                    ])
                    ->when(config('demo.enabled'), fn($field) => $field->fill(config('demo.email'))),
                AuthPasswordField::make('Пароль', 'password')
                    ->when(config('demo.enabled'), fn($field) => $field->customAttributes([
                        'x-init' => '$el.value = "' . Cache::get('demo_current_password', '') . '"',
                        'autocomplete' => 'current-password',
                    ]))
                    ->withoutWrapper()
                    ->required(),

                Switcher::make('Запомнить', 'remember')
            ])->submit('Войти', [
                'style'=>'width: 100% !important; border-radius: 9999px !important; background-color: #828df8 !important; color: #ffffff !important; padding: 0.75rem 1rem !important; font-size: 1rem !important; font-weight: 500 !important; border: none !important; transition: background-color 0.2s ease-in-out !important; cursor: pointer; margin-top: 1.5rem;'
            ])
        ];
    }
}
