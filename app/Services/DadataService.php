<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DadataService
{
    private string $token;
    private string $secret;
    private string $url;
    private int $timeout;

    public function __construct()
    {
        $this->token = config('services.dadata.token');
        $this->secret = config('services.dadata.secret');
        $this->url = config('services.dadata.url');
        $this->timeout = (int) config('services.dadata.timeout', 10);
    }

    public function searchOrganizations(string $query, int $count = 5): array
    {
        if (strlen($query) < 3) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Token ' . $this->token,
                    'X-Secret' => $this->secret,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url, [
                    'query' => $query,
                    'count' => $count,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['suggestions'] ?? [];
            }

            Log::error('Dadata API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('Dadata service exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);

            return [];
        }
    }
}