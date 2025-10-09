<?php

namespace App\MoonShine\Pages;

use App\Models\Setting;
use MoonShine\Core\Pages\Page;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Number;

class SettingsPage extends Page
{
    protected string $title = 'Настройки';
    protected ?array $breadcrumbs = ['Настройки'];

    public function components(): array
    {
        $settings = Setting::whereIn('key', [
            'face_recognition',
            'drive_limit',
            'stream_autoresume'
        ])->pluck('value', 'key');

        return [
            FormBuilder::make()
                ->action(route('settings.store'))
                ->fill([
                    'face_recognition' => (bool)$settings->get('face_recognition', false),
                    'drive_limit' => $settings->get('drive_limit', 100),
                    'stream_autoresume' => (bool)$settings->get('stream_autoresume', false),

                ])
                ->fields([
                    Box::make('Распознание личности', [
                        Box::make('')->customView('components.warning-alert'),
                        Checkbox::make('Я понимаю риски и хочу включить функцию', 'face_recognition')
                    ]),

                    Box::make('Системные параметры', [
                        Number::make('Порог оставшегося места на диске(МБ)', 'drive_limit')
                            ->default(100)
                            ->min(100),
                        Checkbox::make('Автоматическое включение потоков после очистки', 'stream_autoresume')
                            ->default(true),
                    ])
                ])
                ->submit('Сохранить настройки')
        ];
    }
}
