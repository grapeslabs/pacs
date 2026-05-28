<?php

namespace App\Services\PhotoArchive;

use App\Models\Person;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class AllPersonPhotosExportService
{
    /**
     * Создает ZIP-архив с фотографиями для всех персон и файлом images.tsv.
     *
     * @return string|null Путь к временному ZIP-файлу или null при отсутствии файлов.
     */

    public function createArchive(): ?string
    {
        $persons = Person::all();
        $disk = Storage::disk('public');

        $zipFileName = 'persons_photos_archive_' . now()->format('Ymd_His') . '.zip';
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir . '/' . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $tsvLines = [];
        $usedNames = []; // для предотвращения коллизий имён внутри архива
        $totalFiles = 0;

        foreach ($persons as $person) {
            $photos = $person->photo;
            if (is_string($photos)) {
                $photos = json_decode($photos, true);
            }
            if (!is_array($photos) || empty($photos)) {
                continue;
            }

            $fullNameParts = array_filter([
                $person->last_name,
                $person->first_name,
                $person->middle_name,
            ]);
            if (empty($fullNameParts)) {
                $baseName = 'person_' . $person->id;
            } else {
                $baseName = implode('_', $fullNameParts);
            }

            $counter = count($photos) > 1 ? 1 : null;
            foreach ($photos as $relativePath) {
                $relativePath = str_replace('\\/', '/', $relativePath);
                if (!$disk->exists($relativePath)) {
                    continue;
                }

                $absolutePath = $disk->path($relativePath);
                $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

                // Уникальное имя файла в архиве с учётом возможных дубликатов
                if ($counter !== null) {
                    $newNameBase = $baseName . '_' . $counter;
                    $counter++;
                } else {
                    $newNameBase = $baseName;
                }

                $finalNameBase = $newNameBase;
                $suffix = 1;
                while (isset($usedNames[$finalNameBase])) {
                    $finalNameBase = $newNameBase . '_' . $suffix;
                    $suffix++;
                }
                $usedNames[$finalNameBase] = true;

                $newFilename = $finalNameBase . '.' . $extension;
                $zip->addFile($absolutePath, $newFilename);
                $totalFiles++;

                $nameWithoutExt = pathinfo($newFilename, PATHINFO_FILENAME);
                $tsvLines[] = $relativePath . "\t" . $nameWithoutExt;
            }
        }

        if ($totalFiles === 0) {
            $zip->close();
            @unlink($zipPath);
            return null;
        }

        $zip->addFromString('images.tsv', implode("\n", $tsvLines));
        $zip->close();

        return $zipPath;
    }
}
