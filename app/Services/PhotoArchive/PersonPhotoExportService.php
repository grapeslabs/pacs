<?php

namespace App\Services\PhotoArchive;

use App\Models\Person;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PersonPhotoExportService
{
    /**
     * Создает ZIP-архив с фотографиями одной персоны и файлом images.tsv.
     *
     * @param Person $person
     * @return string|null Путь к временному файлу архива (в storage/app/temp) или null при ошибке.
     */
    public function createArchive(Person $person): ?string
    {
        $photos = $person->photo;
        if (is_string($photos)) {
            $photos = json_decode($photos, true);
        }

        if (!is_array($photos) || empty($photos)) {
            return null; // нет фото
        }

        $disk = Storage::disk('public');
        $existing = [];
        foreach ($photos as $relativePath) {
            $relativePath = str_replace('\\/', '/', $relativePath);
            if ($disk->exists($relativePath)) {
                $existing[] = $relativePath;
            }
        }

        if (empty($existing)) {
            return null; // все файлы отсутствуют
        }

        $fullNameParts = array_filter([
            $person->last_name,
            $person->first_name,
            $person->middle_name,
        ]);
        $baseName = implode('_', $fullNameParts);

        $zipFileName = 'person_' . $person->id . '_' . uniqid() . '.zip';
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipTempPath = $tempDir . '/' . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $tsvLines = [];
        $counter = count($existing) > 1 ? 1 : null;

        foreach ($existing as $relativePath) {
            $absolutePath = $disk->path($relativePath);
            $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

            if ($counter !== null) {
                $newFilename = $baseName . '_' . $counter . '.' . $extension;
                $counter++;
            } else {
                $newFilename = $baseName . '.' . $extension;
            }

            $zip->addFile($absolutePath, $newFilename);
            $nameWithoutExt = pathinfo($newFilename, PATHINFO_FILENAME);
            $tsvLines[] = $relativePath . "\t" . $nameWithoutExt;
        }

        $zip->addFromString('images.tsv', implode("\n", $tsvLines));
        $zip->close();

        return $zipTempPath;
    }
}
