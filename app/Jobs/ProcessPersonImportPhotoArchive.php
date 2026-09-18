<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\PhotoArchive\PersonPhotoImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;


class ProcessPersonImportPhotoArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $zipFilePath,
        protected ?int $userId = null,
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

        $imported = 0;
        $errors = 0;

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            Log::info("Обработка файла : {$filename}"  );
            if (!in_array($ext, $allowedExtensions)) {
                continue;
            }

            try {
                $service->importPhoto($filename, $filePath);
                $imported++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Ошибка импорта фото из архива', [
                    'filename' => $filename,
                    'error'    => $e->getMessage(),
                ]);
                // Продолжаем обработку других файлов
            }
        }

        $this->deleteDirectory($extractDir);
        Storage::disk('local')->delete($this->zipFilePath);

        $this->notifyDone($imported, $errors);
    }

    private function notifyDone(int $imported, int $errors): void
    {
        $user = $this->userId ? User::find($this->userId) : null;

        if (! $user) {
            return;
        }

        $personsUrl = moonshine()->getResources()->findByUri('person-resource')?->getUrl();

        $summary = $errors > 0
            ? "Загружено фото: {$imported}, не удалось: {$errors}."
            : "Загружено фото: {$imported}.";

        $user->notify(new SystemNotification(
            $errors > 0 ? ($imported > 0 ? 'warning' : 'error') : 'info',
            "Импорт фотографий персон завершён. {$summary} {link_0}",
            $personsUrl ? [['url' => $personsUrl, 'label' => 'Открыть раздел «Персоны»']] : [],
        ));
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

    public function failed(Throwable $e): void
    {
        Log::error('ProcessPersonImportPhotoArchive: импорт не выполнен', [
            'error' => $e->getMessage(),
        ]);

        $user = $this->userId ? User::find($this->userId) : null;

        $user?->notify(new SystemNotification(
            'error',
            'Не удалось импортировать архив фотографий персон. Проверьте формат архива и повторите попытку.',
        ));
    }
}
