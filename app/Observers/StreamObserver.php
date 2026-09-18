<?php

namespace App\Observers;

use App\Jobs\ProvisionStream;
use App\Jobs\UpdateStreamProvisioning;
use App\Models\Stream;
use App\Models\VideoWall;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticLprService;
use App\Services\VideoAnalyticService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MoonShine\Laravel\MoonShineAuth;

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
    }

    public function created(Stream $stream): void
    {
        ProvisionStream::dispatch($stream->id, MoonShineAuth::getGuard()->id())
            ->afterCommit();
    }

    public function updating(Stream $stream): void
    {
        if ($stream->isDirty('va_options')) {
            $this->normalizeVaOptions($stream);
        }
    }

    public function updated(Stream $stream): void
    {
        $rtspOrArchiveChanged = $stream->wasChanged(['rtsp', 'archive_time']);
        $activeChanged = $stream->wasChanged('is_active');
        $vaOptionsChanged = $stream->wasChanged('va_options');

        if (! $rtspOrArchiveChanged && ! $activeChanged && ! $vaOptionsChanged) {
            return;
        }

        UpdateStreamProvisioning::dispatch($stream->id, $rtspOrArchiveChanged, $activeChanged, $vaOptionsChanged)
            ->afterCommit();
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

        $this->removeFromVideoWalls($stream);
    }

    private function removeFromVideoWalls(Stream $stream): void
    {
        foreach (VideoWall::all() as $wall) {
            $layout = array_values(array_filter(
                array_map(static fn (array $column): array => [
                    'w' => $column['w'] ?? 1,
                    'items' => array_values(array_filter(
                        $column['items'] ?? [],
                        static fn ($item): bool => is_array($item) && (int) ($item['id'] ?? 0) !== $stream->id
                    )),
                ], $wall->layout ?? []),
                static fn (array $column): bool => $column['items'] !== []
            ));

            if ($layout !== ($wall->layout ?? [])) {
                $wall->update(['layout' => $layout]);
            }
        }
    }

    private function normalizeVaOptions(Stream $stream): void
    {
        $options = $stream->va_options ?? [];

        $changed = false;

        if (empty($options['global_enable'])) {
            $options['is_face_detection'] = 0;
            $options['is_face_recognition'] = 0;
            $options['is_motion_detection'] = 0;
            if (config('services.lpr.enabled')) {
                $options['is_plate_recognition'] = 0;
            }
            $changed = true;
        }

        if (empty($options['is_plate_recognition']) && ! empty($options['is_plate_autocollect'])) {
            $options['is_plate_autocollect'] = 0;
            $changed = true;
        }

        if ($changed) {
            $stream->va_options = $options;
        }
    }
}
