<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoAnalyticLprService extends BaseAnalyticService
{
    private string $rtsp;

    public function __construct()
    {
        $this->url = config('services.lpr.url');
        $this->rtsp = config('services.ms.rtsp_url');
        $this->timeout = (int)config('services.lpr.timeout', 10);
    }

    public function cameraList(): array
    {
        return $this->request('/api/v1/camera/list');
    }

    public function cameraCreate(string $cameraUid, string $name = '', ?string $description = '', array $vaOptions = []): array
    {
        $data = [
            'stream_info' => [
                'id' => $cameraUid,
                'url' => "{$this->rtsp}/rtsp/{$cameraUid}",
                'name' => $name,
                'description' => $description ?? '',
                'timedelay' => config('services.lpr.timedelay'),
                'resize' => 1,
            ],
            'recognition_plate' => [
                'is_recognition' => (int)($vaOptions['is_plate_recognition'] ?? 0),
                'threshold' => $vaOptions['plate_recognition_sensitivity'] ?? 75,
                'zone' => $this->zoneToPixels($vaOptions),
            ],
        ];

        return $this->request('/api/v1/camera/create', $data, 'post');
    }

    public function cameraDelete(string $cameraUid): array
    {
        return $this->request('/api/v1/camera/suspend', ['cam_id' => $cameraUid], 'post');
    }

    public function carList(): array
    {
        return $this->request('/api/v1/car/list');
    }

    public function carInfo(string $plate): array
    {
        return $this->request('/api/v1/car/info', ['plate' => $plate]);
    }

    public function carCreate(string $plate, string $description = ''): array
    {
        return $this->request('/api/v1/car/add', ['plate' => $plate, 'desc' => $description], 'post');
    }

    public function carDelete(string $plate): array
    {
        return $this->request('/api/v1/car/del', ['plate' => $plate], 'delete');
    }

    private function zoneToPixels(array $vaOptions): array
    {
        if (empty($vaOptions['has_plate_recognition_zone']) || empty($vaOptions['plate_recognition_zone'])) {
            return [];
        }

        $z = $vaOptions['plate_recognition_zone'];
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
}
