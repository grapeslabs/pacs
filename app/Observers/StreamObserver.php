<?php
namespace App\Observers;

use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class StreamObserver
{
    public function __construct(
        protected MediaServerService $mediaServer,
        protected VideoAnalyticService $vas
    ) {}

    public function creating(Stream $stream): void
    {
        $stream->storage_id = (string) Str::uuid();

        try {
            $stream->uid = $this->mediaServer->createStream(
                $stream->rtsp,
                $stream->storage_id,
                (int) $stream->archive_time
            );

            if (!$stream->is_active) {
                $this->mediaServer->pauseStream($stream->uid);
            }

            if ($stream->is_recognize) {
                $this->createVasStream($stream);
            }

        } catch (Exception $e) {
            if (!empty($stream->uid)) {
                try { $this->mediaServer->deleteStream($stream->uid); } catch (\Throwable $t) {}
            }
            Log::error("Ошибка создания: " . $e->getMessage());
            throw $e;
        }
    }

    public function updating(Stream $stream): void
    {
        if ($stream->isDirty(['rtsp', 'archive_time'])) {
            $this->mediaServer->updateStream($stream->uid, $stream->storage_id,$stream->rtsp, (int)$stream->archive_time);
        }

        if ($stream->isDirty('is_active')) {
            if ($stream->is_active) {
                $this->mediaServer->resumeStream($stream->uid);
            } else {
                $this->mediaServer->pauseStream($stream->uid);
            }
        }

        $this->handleVasUpdate($stream);
    }

    public function deleting(Stream $stream): void
    {
        $this->mediaServer->deleteStream($stream->uid);

        $result = $this->vas->cameraDelete($stream->uid);
        if (empty($result['ok'])) {
            Log::warning("Ошибка удаления из VAS", ['uid' => $stream->uid]);
        }
    }

    private function handleVasUpdate(Stream $stream): void
    {
        $wasRecognized = (bool) $stream->getOriginal('is_recognize');
        $isRecognizedNow = (bool) $stream->is_recognize;

        if ($wasRecognized && !$isRecognizedNow) {
            $this->deleteVasStream($stream);
            return;
        }

        if (!$wasRecognized && $isRecognizedNow) {
            $this->createVasStream($stream);
            return;
        }

        if ($wasRecognized && $isRecognizedNow) {
            if ($stream->isDirty(['name', 'location', 'rtsp'])) {
                $this->createVasStream($stream);
            }
        }
    }

    private function createVasStream(Stream $stream): void
    {
        $result = $this->vas->cameraCreate($stream->storage_id, $stream->uid, $stream->name, $stream->location);
        if (empty($result['ok'])) {
            Log::error("Ошибка VAS cameraCreate", $result ?? []);
            throw new Exception("Ошибка при добавлении потока в систему видео-аналитики");
        }
    }

    private function deleteVasStream(Stream $camera): void
    {
        $result = $this->vas->cameraDelete($camera->uid);
        if (empty($result['ok'])) {
            Log::error("Ошибка VAS cameraDelete", $result ?? []);
            throw new Exception('Не удалось отключить поток от системы видео-аналитики');
        }
    }
}
