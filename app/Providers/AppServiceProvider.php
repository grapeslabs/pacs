<?php

namespace App\Providers;

use App\Models\Car;
use App\Models\Person;
use App\Models\Stream;
use App\Observers\CarObserver;
use App\Observers\PersonObserver;
use App\Observers\StreamObserver;
use App\Models\Key;
use App\Observers\KeyObserver;
use App\Services\MenuService;
use App\Services\Otp\OtpManager;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
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

        $this->app->singleton(MenuService::class);
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
        Car::observe(CarObserver::class);
        $this->invalidateMenuBadgeCacheOnChange();
        if (config('app.docker_domain')) {
            URL::forceScheme('https');
        }

        Queue::before(function (JobProcessing $event): void {
            Log::info('Задание принято в обработку', [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'attempt' => $event->job->attempts(),
            ]);
        });

        Queue::after(function (JobProcessed $event): void {
            Log::info('Задание выполнено', [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
            ]);
        });

        Queue::failing(function (JobFailed $event): void {
            Log::error('Задание завершилось ошибкой', [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'attempt' => $event->job->attempts(),
                'error' => $event->exception->getMessage(),
            ]);
        });
        $this->app->afterResolving(AssetManagerContract::class, function (AssetManagerContract $assets) {
            $assets->add([
                InlineCss::make('<link rel="preload" href="/css/moonshine-custom.css" as="style">')
            ]);
        });
    }

    private function invalidateMenuBadgeCacheOnChange(): void
    {
        $models = collect($this->app->make(MenuService::class)->sections())
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('model')
            ->filter()
            ->unique();

        foreach ($models as $model) {
            $events = ['created', 'deleted'];
            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $events[] = 'restored';
                $events[] = 'forceDeleted';
            }

            foreach ($events as $event) {
                $model::$event(static fn () => Cache::forget('menu:count:' . $model));
            }
        }
    }
}
