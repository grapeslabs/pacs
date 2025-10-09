<?php

return [

    'default' => env('OTP_DRIVER', 'truthy'),

    'connections' => [
        'truthy' => [
            'driver' => 'truthy'
        ],
        'falsy' => [
            'driver' => 'falsy'
        ],
        'flashcalls' => [
            'driver' => 'grapesFlashcalls',
            'url' => env('FLASHCALLS_URL', ''),
            'token' => env('FLASHCALLS_TOKEN', ''),
        ]
    ]
];
