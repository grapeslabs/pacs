<?php

namespace App\Jobs;

use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticLprService;
use App\Services\VideoAnalyticService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateStreamProvisioning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60];
    public int $timeout = 60;

    public function __construct(
        private int $streamId,
        private bool $rtspOrArchiveChanged,
        private bool $activeChanged,
        private bool $vaOptionsChanged,
    ) {
    }

    public function handle(
        MediaServerService $mediaServer,
        VideoAnalyticService $vas,
        VideoAnalyticLprService $lpr,
    ): void {
        $stream = Stream::query()->find($this->streamId);

        if (! $stream) {
            return;
        }

        if ($this->rtspOrArchiveChanged) {
            $mediaServer->updateStream($stream->uid, $stream->rtsp, (int) $stream->archive_time);
        }

        if ($this->activeChanged) {
            $stream->is_active
                ? $mediaServer->resumeStream($stream->uid)
                : $mediaServer->pauseStream($stream->uid);
        }

        if ($this->vaOptionsChanged) {
            $vaOptions = $stream->va_options ?? [];

            if (config('services.va.enabled')) {
                $vas->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
            }

            if (config('services.lpr.enabled')) {
                $lpr->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
            }
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('UpdateStreamProvisioning: не удалось обновить камеру на внешних сервисах', [
            'stream_id' => $this->streamId,
            'error' => $e->getMessage(),
        ]);
    }
}
