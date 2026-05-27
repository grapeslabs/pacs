<?php

namespace App\Services;

use App\Models\Stream;
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

    public function cameraCreate($camera_uid, $name='', $description='', $va_options=[])
    {
        $data = [
            "stream_info" => [
                'id' => $camera_uid,
                'url' => "$this->rtsp/rtsp/$camera_uid",
                'name' => $name,
                'description' => $description,
                'timedelay' => config('services.va.timedelay'),
                "resize" => 1,
            ],
            'detection_face' => [
                'is_detection' => $va_options['is_face_detection'],
                'min_area' => 1600,
                'threshold' => $va_options['face_recognition_sensitivity']??75,
                'face_width_max' => 45,
                "zone" => $this->zoneToPixels($va_options),
                'is_recognize' => $va_options['is_face_recognition'],
                'moving_duration_after' => 4,
                'cache_face_time' => 30,
                'cache_face_max' => 20,
            ],
            "detection_figure" => [
                "is_active" => $va_options['is_motion_detection'],
                "direction" => $va_options['motion_detection_direction']??'A',
                "zones" => $this->zonesToPixels($va_options),
            ],
        ];
        return $this->request('/api/c1/create', $data, "post");
    }

    private function zoneToPixels(array $va_options): array
    {
        if (empty($va_options['has_face_detection_zone']) || empty($va_options['face_detection_zone'])) {
            return [];
        }

        $z = $va_options['face_detection_zone'];
        if (is_string($z)) {
            $z = json_decode($z, true);
        }
        if (!is_array($z)) {
            return [];
        }
        $w = $va_options['video_width']  ?? 1920;
        $h = $va_options['video_height'] ?? 1080;

        return [
            'x1' => (int) round($z['x1'] * $w),
            'y1' => (int) round($z['y1'] * $h),
            'x2' => (int) round($z['x2'] * $w),
            'y2' => (int) round($z['y2'] * $h),
        ];
    }

    private function zonesToPixels(array $va_options): array
    {
        if (empty($va_options['has_motion_detection_zone']) || empty($va_options['motion_detection_zones'])) {
            return [];
        }

        $z = $va_options['motion_detection_zones'];
        if (is_string($z)) {
            $z = json_decode($z, true);
        }
        if (!is_array($z)) {
            return [];
        }

        $w = $va_options['video_width']  ?? 1920;
        $h = $va_options['video_height'] ?? 1080;
        $result = [];

        foreach ($z as $group) {
            $type  = $group['type']  ?? null;
            $zones = $group['zones'] ?? [];

            if (!$type || !is_array($zones)) {
                continue;
            }

            foreach ($zones as $zone) {
                if ($type === 'rectangles' || $type === 'lines') {
                    $result[$type][] = [
                        'x1' => (int) round($zone['x1'] * $w),
                        'y1' => (int) round($zone['y1'] * $h),
                        'x2' => (int) round($zone['x2'] * $w),
                        'y2' => (int) round($zone['y2'] * $h),
                    ];
                } elseif ($type === 'polygons' && is_array($zone)) {
                    $points = [];
                    foreach ($zone as $point) {
                        $points[] = [
                            'x' => (int) round($point['x1'] * $w),
                            'y' => (int) round($point['y1'] * $h),
                        ];
                    }
                    $result['polygons'][] = $points;
                }
            }
        }

        return $result;
    }


    public function handleStreamUpdate(Stream $stream): void
    {
        $options = $stream->va_options ?? [];

        if (empty($options['global_enable'])) {
            $options['is_face_detection'] = 0;
            $options['is_motion_detection'] = 0;
            $stream->va_options = $options;
        }

        $this->cameraCreate($stream->uid, $stream->name, $stream->location, $stream->va_options ?? []);
    }

    public function cameraDelete($camera_uid)
    {
        return $this->request('/api/c1/suspend', ['cam_id' => $camera_uid], "post");
    }

    public function personList()
    {
        return $this->request('/api/v1/person/getinfo');
    }

    public function personInfo($person_Uid)
    {
        return $this->request('/api/v1/person/add', ['person_id' => $person_Uid], 'post');
    }

    public function personCreate(string $name, array $photoPaths, string|int|null $person_uid = null)
    {
        if (empty($photoPaths)) return [];

        try {
            $request = Http::timeout($this->timeout);
            foreach ($photoPaths as $path) {
                $request->attach('photos', fopen($path, 'r'), basename($path));
            }

            $data = ['user_id' => '1', 'desc' => $name];
            if ($person_uid) $data['person_id'] = $person_uid;
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

    public function personDelete($person_Uid)
    {
        return $this->request('/api/v1/person/del', ['person_id' => $person_Uid], 'delete');
    }
}
