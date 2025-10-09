<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MediaServerService
{
    private string $baseUrl;
    private string $externalUrl;
    private int $timeout;


    public function __construct()
    {
        $this->baseUrl = config('services.ms.url');
        $this->timeout = config('services.ms.timeout');
        $this->externalUrl = config('app.url');
    }

    private function request(string $uri, array $data = [], string $method = "post", bool $ignoreNotFound = false): array
    {
        $url = $this->baseUrl . $uri;

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->{$method}($url, $data);

            $responseData = $response->json() ?? [];

            if ($response->status() === 404) {
                if ($ignoreNotFound) {
                    return ['status' => 'success'];
                }
                throw new Exception("Эндпоинт или поток не найден (HTTP 404).");
            }

            if (isset($responseData['status']) && $responseData['status'] === 'error') {
                throw new Exception($responseData['error'] ?? 'Неизвестная ошибка Media Server');
            }

            $response->throw();

            return $responseData;

        } catch (\Throwable $e) {
            Log::error('MediaServer exception', [
                'method' => $method,
                'uri'    => $uri,
                'data'   => $data,
                'error'  => $e->getMessage(),
            ]);
            throw new Exception("Сбой Media Server: " . $e->getMessage());
        }
    }

    public function createStream(string $rtspUrl, string $storageId, int $depthHours): string
    {
        $data = [
            'url' => $rtspUrl,
            'output_dir' => '/data/streams/' . $storageId,
            'segment_duration' => 10,
            'enable_hls' => true,
            'enable_rtsp' => true,
            'mount_path' => '/rtsp/' . $storageId,
            'depthhours' => $depthHours,
        ];

        $response = $this->request('/api/v1/stream/add', $data);

        if (empty($response['data']['stream_uid'])) {
            throw new Exception("Media Server не вернул stream_uid при создании.");
        }

        return $response['data']['stream_uid'];
    }

    public function updateStream(string $streamUid, string $storageId, string $rtspUrl, int $depthHours): array
    {
        $data = [
            'stream_uid' => $streamUid,
            'url' => $rtspUrl,
            'depthhours' => $depthHours,
            'enable_hls' => true,
            'enable_rtsp' => true,
            'mount_path' => '/rtsp/' . $storageId,
        ];

        return $this->request('/api/v1/stream/update', $data);
    }

    public function deleteStream(string $streamUid): array
    {
        return $this->request('/api/v1/stream/del', ['stream_uid' => $streamUid], 'delete', true);
    }

    public function pauseStream(string $streamUid): array
    {
        return $this->request('/api/v1/stream/pause', ['stream_uid' => $streamUid]);
    }

    public function resumeStream(string $streamUid): array
    {
        return $this->request('/api/v1/stream/resume', ['stream_uid' => $streamUid]);
    }

    public function downloadArchive(string $streamUid, $startTime, $endTime)
    {
        return $this->request("/api/v1/stream/$streamUid/archive/download?" . http_build_query([
                'startTime' => $startTime,
                'endTime' => $endTime,
            ]));
    }

    public function downloadStatus($streamUid, $requestId)
    {
        return $this->request("/api/v1/stream/$streamUid/archive/status/{$requestId}", method: 'get');
    }

    public function downloadArchiveFile(string $streamUid, $requestId)
    {
        return [
            'url'=>"$this->externalUrl/media/api/v1/stream/{$streamUid}/archive/download/{$requestId}.mp4"
        ];
    }

}
