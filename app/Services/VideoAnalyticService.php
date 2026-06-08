<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoAnalyticService extends BaseAnalyticService
{
    private string $rtsp;

    public function __construct()
    {
        $this->url = config('services.va.url');
        $this->rtsp = config('services.ms.rtsp_url');
        $this->timeout = (int)config('services.va.timeout', 10);
    }

    public function cameraList(): array
    {
        return $this->request('/api/v1/camera/list');
    }

    public function cameraCreate(string $cameraUid, string $name = '', $description = '', array $vaOptions = []): array
    {
        $data = [
            'stream_info' => [
                'id' => $cameraUid,
                'url' => "{$this->rtsp}/rtsp/{$cameraUid}",
                'name' => $name,
                'description' => $description,
                'timedelay' => config('services.va.timedelay'),
                'resize' => 1,
            ],
            'detection_face' => [
                'is_detection' => (int)($vaOptions['is_face_detection'] ?? 0),
                'min_area' => 1600,
                'threshold' => $vaOptions['face_recognition_sensitivity'] ?? 75,
                'face_width_max' => 45,
                'zone' => $this->zoneToPixels($vaOptions),
                'is_recognize' => (int)($vaOptions['is_face_recognition'] ?? 0),
                'moving_duration_after' => 4,
                'cache_face_time' => 30,
                'cache_face_max' => 20,
            ],
            'detection_figure' => [
                'is_active' => (int)($vaOptions['is_motion_detection'] ?? 0),
                'direction' => $vaOptions['motion_detection_direction'] ?? 'A',
                'zones' => $this->zonesToPixels($vaOptions),
            ],
        ];

        return $this->request('/api/v1/camera/create', $data, 'post');
    }

    public function cameraDelete(string $cameraUid): array
    {
        return $this->request('/api/v1/camera/suspend', ['cam_id' => $cameraUid], 'post');
    }

    public function personList(): array
    {
        return $this->request('/api/v1/person/getinfo');
    }

    public function personInfo(string $personUid): array
    {
        return $this->request('/api/v1/person/add', ['person_id' => $personUid], 'post');
    }

    public function personCreate(string $name, array $photoPaths, string|int|null $personUid = null): array
    {
        if (empty($photoPaths)) {
            return [];
        }

        try {
            $http = Http::timeout($this->timeout);
            foreach ($photoPaths as $path) {
                $http->attach('photos', fopen($path, 'r'), basename($path));
            }

            $data = ['user_id' => '1', 'desc' => $name];
            if ($personUid !== null) {
                $data['person_id'] = $personUid;
            }

            $response = $http->post($this->url . '/api/v1/person/add', $data);
            $response->throw();

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('VideoAnalyticService exception in personCreate', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function personDelete(string $personUid): array
    {
        return $this->request('/api/v1/person/del', ['person_id' => $personUid], 'delete');
    }

    private function zoneToPixels(array $vaOptions): array
    {
        if (empty($vaOptions['has_face_detection_zone']) || empty($vaOptions['face_detection_zone'])) {
            return [];
        }

        $z = $vaOptions['face_detection_zone'];
        if (is_string($z)) {
            $z = json_decode($z, true);
        }
        if (!is_array($z)) {
            return [];
        }

        $w = $vaOptions['video_width'] ?? 1920;
        $h = $vaOptions['video_height'] ?? 1080;

        return [
            'x1' => (int)round($z['x1'] * $w),
            'y1' => (int)round($z['y1'] * $h),
            'x2' => (int)round($z['x2'] * $w),
            'y2' => (int)round($z['y2'] * $h),
        ];
    }

    private function zonesToPixels(array $vaOptions): array
    {
        if (empty($vaOptions['has_motion_detection_zone']) || empty($vaOptions['motion_detection_zones'])) {
            return [];
        }

        $z = $vaOptions['motion_detection_zones'];
        if (is_string($z)) {
            $z = json_decode($z, true);
        }
        if (!is_array($z)) {
            return [];
        }

        $w = $vaOptions['video_width'] ?? 1920;
        $h = $vaOptions['video_height'] ?? 1080;
        $result = [];

        foreach ($z as $group) {
            $type = $group['type'] ?? null;
            $zones = $group['zones'] ?? [];

            if (!$type || !is_array($zones)) {
                continue;
            }

            foreach ($zones as $zone) {
                if ($type === 'rectangles' || $type === 'lines') {
                    $result[$type][] = [
                        'x1' => (int)round($zone['x1'] * $w),
                        'y1' => (int)round($zone['y1'] * $h),
                        'x2' => (int)round($zone['x2'] * $w),
                        'y2' => (int)round($zone['y2'] * $h),
                    ];
                } elseif ($type === 'polygons' && is_array($zone)) {
                    $points = [];
                    foreach ($zone as $point) {
                        $points[] = [
                            'x' => (int)round($point['x1'] * $w),
                            'y' => (int)round($point['y1'] * $h),
                        ];
                    }
                    $result['polygons'][] = $points;
                }
            }
        }

        return $result;
    }
}
