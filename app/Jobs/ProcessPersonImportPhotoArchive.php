<?php

namespace App\Jobs;

use App\Services\PhotoArchive\PersonPhotoImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;


class ProcessPersonImportPhotoArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $zipFilePath // путь в storage, куда загрузили архив
    ) {}

    public function handle(PersonPhotoImportService $service): void
    {
        $zip = new ZipArchive();

        $zipFullPath = Storage::disk('local')->path($this->zipFilePath);
        if ($zip->open($zipFullPath) !== true) {
            Log::error("Не удалось открыть архив: {$zipFullPath}");
            throw new \Exception("Не удалось открыть архив: {$zipFullPath}");
        }

        $extractDir = storage_path('app/temp/import_' . uniqid());
        mkdir($extractDir, 0755, true);

        $zip->extractTo($extractDir);
        $zip->close();

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $files = glob($extractDir . '/*');
        Log::info("Обнаружено :" , [count($files)] , ' файлов');
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            Log::info("Обработка файла : {$filename}"  );
            if (!in_array($ext, $allowedExtensions)) {
                continue;
            }

            try {
                $service->importPhoto($filename, $filePath);
            } catch (\Exception $e) {
                Log::error('Ошибка импорта фото из архива', [
                    'filename' => $filename,
                    'error'    => $e->getMessage(),
                ]);
                // Продолжаем обработку других файлов
            }
        }

        $this->deleteDirectory($extractDir);
        Storage::disk('local')->delete($this->zipFilePath);
    }

    protected function deleteDirectory($dir): void
    {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
