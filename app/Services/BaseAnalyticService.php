<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseAnalyticService
{
    protected string $url;
    protected int $timeout;

    protected function request(string $uri, array $data = [], string $method = 'get'): array
    {
        $method = strtolower($method);
        $params = array_merge(['user_id' => '1'], $data);
        if(config('app.debug')) {
            Log::debug(static::class . ' exception', [
                'method' => $method,
                'uri' => $uri,
                'payload' => $params,
            ]);
        }
        try {
            $fullUrl = $this->url . $uri;
            $http = Http::timeout($this->timeout);

            if (in_array($method, ['get', 'delete'])) {
                $fullUrl .= '?' . http_build_query($params);
                $response = $http->{$method}($fullUrl);
            } else {
                $response = $http->{$method}($fullUrl, $params);
            }
            if(config('app.debug')) {
                Log::debug(static::class . ':', $response->throw()->json());
            }
            if ($response->status() === 404) {
                return ['ok' => true, 'note' => 'not_found_ignored'];
            }

            return $response->throw()->json();

        } catch (\Throwable $e) {
            Log::error(static::class . ' exception', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
                'payload' => $params,
            ]);

            return [];
        }
    }
}
