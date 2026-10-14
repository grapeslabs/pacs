<?php

namespace App\Console\Commands;

use App\Models\Stream;
use App\Services\MediaServerService;
use Illuminate\Console\Command;
use Throwable;

class EnableStreamWebrtc extends Command
{
    protected $signature = 'streams:enable-webrtc';

    protected $description = 'Включить WebRTC (WHEP) у всех существующих видеопотоков на медиасервере';

    public function handle(MediaServerService $mss): int
    {
        $streams = Stream::whereNotNull('uid')->get();

        if ($streams->isEmpty()) {
            $this->info('Видеопотоков не найдено');

            return 0;
        }

        $failed = 0;

        foreach ($streams as $stream) {
            try {
                $mss->updateStream($stream->uid, $stream->rtsp, (int) $stream->archive_time);
                $this->line("WebRTC включён: {$stream->uid} · {$stream->name}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("{$stream->uid} · {$stream->name}: {$e->getMessage()}");
            }
        }

        $this->info('Обработано потоков: ' . $streams->count() . ', ошибок: ' . $failed);

        return $failed > 0 ? 1 : 0;
    }
}
