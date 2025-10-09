<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoAnalyticService
{
    private string $url;
    private string $rtsp;
    private int $timeout;


    public function __construct()
    {
        $this->url = config('services.va.url');
        $this->rtsp = config('services.ms.rtsp_url');
        $this->timeout = (int) config('services.va.timeout', 10);
    }

    private function request(string $uri, array $data = [], string $method = 'get'): array
    {
        $method = strtolower($method);
        $params = array_merge(['user_id' => '1'], $data);

        try {
            $pendingRequest = Http::timeout($this->timeout);
            $fullUrl = $this->url . $uri;

            if (in_array($method, ['get', 'delete'])) {
                $fullUrl .= '?' . http_build_query($params);
                $response = $pendingRequest->{$method}($fullUrl);
            } else {
                $response = $pendingRequest->{$method}($fullUrl, $params);
            }

            if ($response->status() === 404) {
                return ['ok' => true, 'note' => 'not_found_ignored'];
            }
            return $response->throw()->json();

        } catch (\Throwable $e) {
            Log::error('VAS Exception', [
                'method'  => $method,
                'uri'     => $uri,
                'error'   => $e->getMessage(),
                'payload' => $params,
            ]);

            return [];
        }
    }

    public function cameraList()
    {
        return $this->request('/api/c1/list');
    }

    public function cameraInfo($camera_uid)
    {
        return $this->request('/api/c1/list', ['cam_id' => $camera_uid]);
    }

    public function cameraCreate($storage_id, $camera_uid='', $name='', $description='')
    {
        $data = [
            'stream_to_parse' => "$this->rtsp/rtsp/$storage_id",
            'cam_id' => $camera_uid,
            'name' => $name,
            'desc' => $description
        ];
        return $this->request('/api/c1/create', $data, "post");
    }

    public function cameraDelete($camera_uid)
    {
        return $this->request('/api/c1/suspend', ['cam_id' => $camera_uid], "post");
    }

    public function personList()
    {
        return $this->request('/api/v1/person/getinfo');
    }

    public function personInfo($person_id)
    {
        return $this->request('/api/v1/person/add', ['person_id' => $person_id], 'post');
    }

    public function personCreate(string $name, array $photoPaths, string|int|null $person_id = null)
    {
        if (empty($photoPaths)) return [];

        try {
            $request = Http::timeout($this->timeout);
            foreach ($photoPaths as $path) {
                $request->attach('photos', fopen($path, 'r'), basename($path));
            }

            $data = ['user_id' => '1', 'desc' => $name];
            if ($person_id) $data['person_id'] = $person_id;
            $response = $request->post($this->url . '/api/v1/person/add', $data);
            $response->throw();
            return $response->json();

        } catch (\Throwable $e) {
            Log::error('VAS exception in personCreate', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function personDelete($person_id)
    {
        return $this->request('/api/v1/person/del', ['person_id' => $person_id], 'delete');
    }
}
