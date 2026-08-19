<?php

namespace App\MoonShine\Pages;

use App\Models\Setting;
use App\MoonShine\InputExtensions\InputRotateToken;
use App\Services\FrpService;
use MoonShine\Core\Pages\Page;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

class SettingsPage extends Page
{
    protected string $title = 'Настройки';
    protected ?array $breadcrumbs = ['Настройки'];

    public function components(): array
    {
        abort_if(
            !MoonShineAuth::getGuard()->user()?->hasPermission(self::class, Ability::VIEW),
            403
        );

        $settings = Setting::whereIn('key', [
            'face_recognition',
            'drive_limit',
            'stream_autoresume',
            'frp_enabled',
        ])->pluck('value', 'key');

        $frp = app(FrpService::class);
        $frpRegistered = $frp->isRegistered();
        $frpInstance = $frp->instanceData();

        return [
            Div::make()->customView('components.profile-header', [
                'title'    => 'Настройки',
                'subtitle' => 'Параметры системы и интеграций',
            ]),
            FormBuilder::make()
                ->action(route('settings.store'))
                ->fill([
                    'face_recognition' => (bool)$settings->get('face_recognition', false),
                    'drive_limit' => $settings->get('drive_limit', 100),
                    'stream_autoresume' => (bool)$settings->get('stream_autoresume', false),
                    'frp_enabled' => (bool)$settings->get('frp_enabled', false),
                    'frp_uuid_display' => $frpInstance['uuid'],
                    'frp_token_display' => $frpInstance['token'],
                    'frp_hint' => $frpRegistered
                        ? 'Укажите UUID и токен в личном кабинете портала, чтобы получить удалённый доступ к этому серверу.'
                        : 'Экземпляр ещё не зарегистрирован на портале — регистрация идёт автоматически, зайдите чуть позже.',
                ])
                ->fields([
                    ...config('services.va.enabled')?[
                        Box::make([
                            $this->sectionHeader('Распознавание личности'),
                            Box::make('')->customView('components.warning-alert'),
                            Checkbox::make('Я понимаю риски и хочу включить функцию', 'face_recognition')
                        ])->class('profile-card'),
                    ]:[],
                    Box::make([
                        $this->sectionHeader('Системные параметры'),
                        Number::make('Порог оставшегося места на диске (МБ)', 'drive_limit')
                            ->default(100)
                            ->min(100),
                        Checkbox::make('Автоматическое включение потоков после очистки', 'stream_autoresume')
                            ->default(true),
                    ])->class('profile-card'),
                    Box::make([
                        $this->sectionHeader('Удалённый доступ (FRP)'),
                        Switcher::make('Включить удалённый доступ', 'frp_enabled')
                            ->canSee(fn () => $frpRegistered),
                        Preview::make('', 'frp_hint'),
                        ...$frpRegistered ? [
                            Text::make('UUID экземпляра', 'frp_uuid_display')
                                ->readonly()
                                ->copy(),
                            Text::make('Токен', 'frp_token_display')
                                ->readonly()
                                ->eye()
                                ->copy()
                                ->extension(new InputRotateToken(route('settings.frp.rotate-token'))),
                        ] : [],
                    ])->class('profile-card'),
                ])
                ->submit('Сохранить настройки', ['class' => 'btn-primary'])
        ];
    }

    private function sectionHeader(string $title, ?string $subtitle = null): Div
    {
        return Div::make()->customView('components.profile-section-header', [
            'title'    => $title,
            'subtitle' => $subtitle,
        ]);
    }
}
