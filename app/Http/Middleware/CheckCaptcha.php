<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use function Laravel\Prompts\confirm;

class CheckCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('authenticate') && $request->isMethod('post') && config('services.yacaptcha.enabled')) {

            $data = $request->validate([
                'smart-token' => 'required|string',
            ]);

            $response = Http::asForm()->post('https://smartcaptcha.yandexcloud.net/validate',[
                'secret' => config('services.yacaptcha.secret'),
                'token'  => $data['smart-token'],
                'ip'     => $request->ip(),
            ]);

            if ($response->status() <> 200) {
                return redirect()->back();
            }
        }

        return $next($request);
    }
}
