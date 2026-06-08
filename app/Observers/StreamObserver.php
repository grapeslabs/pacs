<?php

namespace App\Observers;

use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticLprService;
use App\Services\VideoAnalyticService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class StreamObserver
{
    public function __construct(
        protected MediaServerService $mediaServer,
        protected VideoAnalyticService $vas,
        protected VideoAnalyticLprService $lpr,
    ) {}

    public function creating(Stream $stream): void
    {
        $stream->uid = (string)Str::uuid();

        try {
            $stream->uid = $this->mediaServer->createStream(
                $stream->rtsp,
                $stream->uid,
                (int)$stream->archive_time
            );

            if (!$stream->is_active) {
                $this->mediaServer->pauseStream($stream->uid);
            }

            $vaOptions = $stream->va_options ?? [];

            if (config('services.va.enabled')) {
                $this->vas->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
            }

            if (config('services.lpr.enabled')) {
                $this->lpr->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
            }

        } catch (Exception $e) {
            if (!empty($stream->uid)) {
                try {
                    $this->mediaServer->deleteStream($stream->uid);
                } catch (\Throwable) {
                }
            }
            Log::error('StreamObserver: error on creating', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updating(Stream $stream): void
    {
        if ($stream->isDirty(['rtsp', 'archive_time'])) {
            $this->mediaServer->updateStream($stream->uid, $stream->rtsp, (int)$stream->archive_time);
        }

        if ($stream->isDirty('is_active')) {
            $stream->is_active
                ? $this->mediaServer->resumeStream($stream->uid)
                : $this->mediaServer->pauseStream($stream->uid);
        }

        if ($stream->isDirty('va_options')) {
            $this->normalizeVaOptions($stream);

            $vaOptions = $stream->va_options ?? [];

            if (config('services.va.enabled')) {
                $this->vas->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
            }

            if (config('services.lpr.enabled')) {
                $this->lpr->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
            }
        }
    }

    public function deleting(Stream $stream): void
    {
        $this->mediaServer->deleteStream($stream->uid);

        if (config('services.va.enabled')) {
            $result = $this->vas->cameraDelete($stream->uid);
            if (empty($result['ok'])) {
                Log::warning('StreamObserver: VA camera delete failed', ['uid' => $stream->uid]);
            }
        }

        if (config('services.lpr.enabled')) {
            $result = $this->lpr->cameraDelete($stream->uid);
            if (empty($result['ok'])) {
                Log::warning('StreamObserver: LPR camera delete failed', ['uid' => $stream->uid]);
            }
        }
    }

    private function normalizeVaOptions(Stream $stream): void
    {
        $options = $stream->va_options ?? [];

        if (empty($options['global_enable'])) {
            $options['is_face_detection'] = 0;
            $options['is_face_recognition'] = 0;
            $options['is_motion_detection'] = 0;
            if (config('services.lpr.enabled')) {
                $options['is_plate_recognition'] = 0;
            }
            $stream->va_options = $options;
        }
    }
}
