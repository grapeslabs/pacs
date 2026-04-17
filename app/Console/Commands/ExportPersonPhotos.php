<?php

namespace App\Console\Commands;

use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportPersonPhotos extends Command
{
    protected $signature = 'person:export-photos {person_id : ID персоны}';
    protected $description = 'Экспорт фотографий персоны по id в ZIP-архив с TSV-файлом';

    public function handle()
    {
        $personId = $this->argument('person_id');
        $person = Person::find($personId);

        if (!$person) {
            $this->error("Запись с ID: {$personId} не найдена.");
            return 1;
        }

        $photos = $person->photo;
        if (is_string($photos)) {
            $photos = json_decode($photos, true);
        }

        if (!is_array($photos) || empty($photos)) {
            $this->error("У персоны {$personId} нет фотографий.");
            return 1;
        }

        $disk = Storage::disk('public');

        $missing = [];
        $existing = [];
        foreach ($photos as $relativePath) {
            $relativePath = str_replace('\\/', '/', $relativePath);
            if ($disk->exists($relativePath)) {
                $existing[] = $relativePath;
            } else {
                $missing[] = $relativePath;
            }
        }

        if (!empty($missing)) {
            $this->warn("Некоторые файлы не найдены: " . implode(', ', $missing));
        }

        if (empty($existing)) {
            $this->error("Не найдено ни одного существующего файла для архивации.");
            return 1;
        }

        $zipFileName = "person_{$personId}.zip";
        $zipTempPath = storage_path("app/temp/{$zipFileName}");
        $exportDir = storage_path('app/temp');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Ошибка создания ZIP-файла.");
            return 1;
        }

        $tsvLines = [];

        foreach ($existing as $relativePath) {
            $absolutePath = $disk->path($relativePath);

            $zip->addFile($absolutePath, $relativePath);

            $baseName = pathinfo($relativePath, PATHINFO_FILENAME);

            $tsvLines[] = $absolutePath . "\t" . $baseName;
        }

        $tsvContent = implode("\n", $tsvLines);
        $zip->addFromString('images.tsv', $tsvContent);

        $zip->close();

        $publicZipPath = "exports/{$zipFileName}";
        Storage::disk('public')->put($publicZipPath, file_get_contents($zipTempPath));

        unlink($zipTempPath);

        $this->info("Ссылка на архив: " . Storage::disk('public')->url($publicZipPath));
        $this->info("Файлов в архиве: " . count($existing));

        return 0;
    }
}
