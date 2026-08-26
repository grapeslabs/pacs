<?php

namespace App\Console\Commands;

use App\Services\LogReaderService;
use Illuminate\Console\Command;

class PruneLogs extends Command
{
    protected $signature = 'logs:prune {--days= : Сколько дней хранить (по умолчанию из config/logs.php)}';

    protected $description = 'Удаляет старые лог-файлы приложения и воркеров (запускается вручную)';

    public function handle(LogReaderService $reader): int
    {
        $days = (int) ($this->option('days') ?: config('logs.keep_days', 14));

        if ($days < 1) {
            $this->error('Срок хранения должен быть не меньше одного дня');

            return 1;
        }

        $threshold = now()->subDays($days)->getTimestamp();
        $removed = 0;
        $freed = 0;

        foreach ($reader->sources() as $key => $source) {
            if (!$source['writable']) {
                continue;
            }

            foreach ($reader->files($key) as $file) {
                if ($file['modified'] >= $threshold) {
                    continue;
                }

                $path = $reader->resolvePath($key, $file['name']);

                if (@unlink($path)) {
                    $removed++;
                    $freed += $file['size'];

                    $this->line("Удалён {$key}/{$file['name']}");
                }
            }
        }

        $this->info(sprintf(
            'Удалено файлов: %d, освобождено %.1f МБ (хранение — %d дн.)',
            $removed,
            $freed / 1048576,
            $days
        ));

        return 0;
    }
}
