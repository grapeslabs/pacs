<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MoonShine\Laravel\Exceptions\MoonShineNotFoundException;
use App\MoonShine\Forms\CustomFiltersForm;
use MoonShine\Laravel\Http\Middleware\Authenticate;
use MoonShine\Laravel\Http\Middleware\ChangeLocale;
use App\Models\User;
use App\MoonShine\Pages\Dashboard;
use MoonShine\Laravel\Pages\ErrorPage;
use App\MoonShine\Pages\LoginPage;
use \MoonShine\Laravel\Forms\LoginForm;
use \App\Http\Middleware\CheckCaptcha;
use \App\Http\Middleware\CleanEmptyFilters;
use \App\Http\Middleware\AuthorizeMoonShineAction;

return [
    'title' => 'GRAPES PACS',
    'logo' => '/icons/logo.svg',
    'logo_small' => '/icons/logo_small.svg',
    'ui' => [
        'theme' => 'light', // Принудительно светлая тема
        'dark_mode' => false, // Отключаем темный режим
    ],


    // Default flags
    'use_migrations' => true,
    'use_notifications' => true,
    'use_database_notifications' => true,
    'use_routes' => true,
    'use_profile' => true,

    // Routing
    'domain' => env('MOONSHINE_DOMAIN'),
    'prefix' => env('MOONSHINE_ROUTE_PREFIX', 'admin'),
    'page_prefix' => env('MOONSHINE_PAGE_PREFIX', 'page'),
    'resource_prefix' => env('MOONSHINE_RESOURCE_PREFIX', 'resource'),
    'home_url' => '/page/dashboard',

    // Error handling
    'not_found_exception' => MoonShineNotFoundException::class,

    // Middleware
    'middleware' => [
        CleanEmptyFilters::class,
        CheckCaptcha::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        ChangeLocale::class,
        AuthorizeMoonShineAction::class,
    ],

    // Storage
    'disk' => 'public',
    'disk_options' => [],
    'cache' => 'file',

    // Authentication and profile
    'auth' => [
        'enabled' => true,
        'guard' => 'moonshine',
        'model' => User::class,
        'middleware' => Authenticate::class,
        'pipelines' => [],
    ],

    // Authentication and profile
    'user_fields' => [
        'username' => 'email',
        'password' => 'password',
        'name' => 'name',
        'avatar' => 'avatar',
    ],

    // Layout, pages, forms
    'layout' => App\MoonShine\Layouts\MoonShineLayout::class,

    'forms' => [
        'login' => LoginForm::class,
        'filters' => CustomFiltersForm::class,
    ],

    'pages' => [
        'dashboard' => App\MoonShine\Pages\Dashboard::class,
        'profile' => \App\MoonShine\Pages\ProfilePage::class,
        'login' => \App\MoonShine\Pages\LoginPage::class,
        'error' => ErrorPage::class,
    ],

    // Localizations
    'locale' => 'ru',
    'locales' => [
        'en',
        'ru'
    ],

];
