<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use MoonShine\Laravel\MoonShineUI;
use MoonShine\Support\Enums\ToastType;
use Symfony\Component\HttpFoundation\Response;

class CheckDiskSpace
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::get('drive_limit_stoped', false)) {
            MoonShineUI::toast(
                'Запись остановлена: диск переполнен. Чтобы возобновить видеопотоки, удалите старые записи или расширьте хранилище.',
                ToastType::ERROR,
                10000
            );
        }

        return $next($request);
    }
}
