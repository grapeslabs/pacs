<?php

return [
    'default_controller_type' => 'ironlogic',

    'controller_types' => [
        'ironlogic' => [
            'driver' => \GrapesLabs\PinvideoSkud\Controllers\IronLogicController::class,
            'protocol' => 'web-json',
        ],
        'z5rweb' => [
            'driver' => \GrapesLabs\PinvideoSkud\Controllers\IronLogicController::class,
            'protocol' => 'web-json',
        ],
        'pinterm' => [
            'driver' => \GrapesLabs\PinvideoSkud\Controllers\PinTermController::class,
            'protocol' => 'web-json',
        ],
        // другие типы контроллеров
    ],

    'routes' => [
        'prefix' => 'api/skud',
        'middleware' => ['api'],
    ],

    'tables' => [
        'skud_controllers' => 'grapeslabs_skud_controllers',
        'skud_events' => 'grapeslabs_skud_events',
        'skud_commands' => 'grapeslabs_skud_commands',
    ],
];