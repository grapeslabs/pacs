<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Forms\LoginForm;
use Illuminate\Support\Facades\Cache;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

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
            ->class('authentication-form')
            ->action(moonshineRouter()->to('authenticate'))
            ->fields([
                Text::make(__('moonshine::ui.login.username'), 'username')
                    ->required()
                    ->customAttributes([
                        'autofocus' => true,
                        'autocomplete' => 'username',
                    ])
                    ->when(config('demo.enabled'), fn($field) => $field->fill(config('demo.email'))),

                Password::make(__('moonshine::ui.login.password'), 'password')
                    ->when(config('demo.enabled'), fn($field) => $field->customAttributes([
                        'x-init' => '$el.value = "' . Cache::get('demo_current_password', '') . '"',
                        'autocomplete' => 'current-password',
                    ]))
                    ->required(),

                Switcher::make(__('moonshine::ui.login.remember_me'), 'remember'),
            ])->submit(__('moonshine::ui.login.login'), [
                'class' => 'btn-primary btn-lg w-full',
            ])
        ];
    }
}
