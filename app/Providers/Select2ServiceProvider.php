<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class Select2ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-select2');

    }
}