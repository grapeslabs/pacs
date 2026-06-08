<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;
use Carbon\Carbon;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY') ?? $request->bearerToken();

        if (!$key) {
            return response()->json(['message' => 'Не указан ключ API'], 401);
        }

        $apiKey = ApiKey::where('key', $key)->where('is_active', true)->first();

        if (!$apiKey) {
            return response()->json(['message' => 'Неверный или неактивный ключ API'], 401);
        }

        if (!$apiKey->is_unlimited && $apiKey->expires_at && Carbon::now()->greaterThan($apiKey->expires_at)) {
            return response()->json(['message' => 'Срок действия ключа API истёк'], 401);
        }

        $request->attributes->add(['api_key' => $apiKey]);

        return $next($request);
    }
}
