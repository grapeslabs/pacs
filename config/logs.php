<?php

return [
    'sources' => [
        'app' => [
            'label' => 'Приложение',
            'path' => storage_path('logs/php'),
            'format' => 'laravel',
            'writable' => true,
            'enabled' => true,
        ],

        'horizon' => [
            'label' => 'Очереди',
            'path' => storage_path('logs/horizon'),
            'format' => 'laravel',
            'writable' => true,
            'enabled' => true,
        ],

        'events' => [
            'label' => 'Обработка событий',
            'path' => storage_path('logs/events'),
            'format' => 'laravel',
            'writable' => true,
            'enabled' => true,
        ],

        'crossings' => [
            'label' => 'Обработка пересечений',
            'path' => storage_path('logs/crossings'),
            'format' => 'laravel',
            'writable' => true,
            'enabled' => (bool) env('ANALYTIC_ENABLED', false),
        ],

        'lpr-rules' => [
            'label' => 'Обработка ГРЗ',
            'path' => storage_path('logs/lpr-rules'),
            'format' => 'laravel',
            'writable' => true,
            'enabled' => (bool) env('LPR_ENABLED', false),
        ],

        'lpr-core' => [
            'label' => 'ГРЗ аналитика',
            'path' => base_path('lpr/logs'),
            'format' => 'plain',
            'writable' => false,
            'enabled' => (bool) env('LPR_ENABLED', false),
        ],

        'analytic-server' => [
            'label' => 'Видеоаналитика - сервер',
            'path' => base_path('analytic/logs/server'),
            'format' => 'plain',
            'writable' => false,
            'enabled' => (bool) env('ANALYTIC_ENABLED', false),
        ],

        'analytic-api' => [
            'label' => 'Видеоаналитика - API',
            'path' => base_path('analytic/logs/api'),
            'format' => 'plain',
            'writable' => false,
            'enabled' => (bool) env('ANALYTIC_ENABLED', false),
        ],

    ],

    'per_page' => 50,

    'max_scan_bytes' => 64 * 1024 * 1024,
    'chunk_size' => 128 * 1024,

    'keep_days' => (int) env('LOG_DAILY_DAYS', 14),
];
