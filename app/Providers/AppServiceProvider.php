<?php

namespace App\Providers;

use App\Models\Person;
use App\Models\Stream;
use App\Observers\PersonObserver;
use App\Observers\StreamObserver;
use App\Models\Key;
use App\Observers\KeyObserver;
use App\Services\Otp\OtpManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use App\Observers\SkudControllerObserver;
use GrapesLabs\PinvideoSkud\Models\SkudEvent;
use App\Observers\SkudEventObserver;
use MoonShine\AssetManager\InlineCss;
use MoonShine\Contracts\AssetManager\AssetManagerContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        app()->singleton(OtpManager::class, function () {
            $config = config('otp');
            return new OtpManager($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SkudController::observe(SkudControllerObserver::class);
        SkudEvent::observe(SkudEventObserver::class);
        Key::observe(KeyObserver::class);
        Person::observe(PersonObserver::class);
        Stream::observe(StreamObserver::class);
        URL::forceScheme('https');
        $this->app->afterResolving(AssetManagerContract::class, function (AssetManagerContract $assets) {
            $assets->add([
                InlineCss::make('<link rel="preload" href="/css/moonshine-custom.css" as="style">')
            ]);
        });
    }
}
