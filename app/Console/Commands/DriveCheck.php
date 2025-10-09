<?php

namespace App\Console\Commands;
use App\Models\Setting;
use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DriveCheck extends Command
{
    protected $signature = 'drive:check';

    public function handle(MediaServerService $mss, VideoAnalyticService $vss): int
    {
        $this->info('Проверка диска...');
        $this->process($mss, $vss);
        $this->info('Проверка диска завершена');
        return 0;
    }

    protected function process(MediaServerService $mss, VideoAnalyticService $vss): void
    {
        $lock = Cache::lock('drive_limit_stoped', 600);
        if(!$lock->get()) {
            return;
        }
        $is_autoresume = Setting::where('key', 'stream_autoresume')->value('value')??true;
        $is_stoped = Cache::get('drive_limit_stoped', false);
        $limitMB = (int) Setting::where('key', 'drive_limit')->value('value')??100;
        $limitBytes = $limitMB * 1048576;
        $freeBytes = disk_free_space(app_path());
        if (!$is_stoped && $freeBytes < $limitBytes) {
            $this->warn('Видеопотоки остановливаются: диск переполнен');
            $streams = Stream::all();
            foreach ($streams as $stream) {
                $mss->pauseStream($stream->uid);
                $vss->cameraDelete($stream->uid);
            }
            Cache::forever('drive_limit_stoped', true);
        } else {
            if($is_stoped && $is_autoresume && $freeBytes > $limitBytes) {
                $streams = Stream::all();
                foreach ($streams as $stream) {
                    if($stream->is_active) {
                        $mss->resumeStream($stream->uid);
                        if($stream->is_recognize) {
                            $vss->cameraCreate($stream->storage_id, $stream->uid, $stream->title, $stream->location);
                        }
                    }
                }
                Cache::forever('drive_limit_stoped', false);
            }
        }
        $lock->release();
    }
}
