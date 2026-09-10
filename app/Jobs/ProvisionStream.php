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
use MoonShine\Laravel\Notifications\MoonShineNotification;
use Throwable;

class ProvisionStream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60];
    public int $timeout = 60;

    public function __construct(
        private int $streamId,
        private int|null $userId = null,
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

        $mediaServer->createStream($stream->rtsp, $stream->uid, (int) $stream->archive_time);

        if (! $stream->is_active) {
            $mediaServer->pauseStream($stream->uid);
        }

        $vaOptions = $stream->va_options ?? [];

        if (config('services.va.enabled')) {
            $vas->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
        }

        if (config('services.lpr.enabled')) {
            $lpr->cameraCreate($stream->uid, $stream->name, $stream->location, $vaOptions);
        }
    }

    public function failed(Throwable $e): void
    {
        $stream = Stream::query()->find($this->streamId);

        Log::error('ProvisionStream: не удалось завести камеру на внешних сервисах', [
            'stream_id' => $this->streamId,
            'uid' => $stream?->uid,
            'error' => $e->getMessage(),
        ]);

        if (! $this->userId) {
            return;
        }

        MoonShineNotification::send(
            'Камера «' . ($stream?->name ?? $this->streamId) . '» не заведена на медиасервере. Проверьте доступность сервисов и сохраните камеру повторно.',
            ids: [$this->userId],
        );
    }
}
