<?php

namespace App\Providers;

use App\Actuators\ActuatorDriverManager;
use Illuminate\Support\ServiceProvider;

class ActuatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActuatorDriverManager::class, function () {
            $manager = new ActuatorDriverManager();

            foreach (config('actuators.drivers', []) as $driverClass) {
                $manager->register($driverClass);
            }

            return $manager;
        });
    }
}
