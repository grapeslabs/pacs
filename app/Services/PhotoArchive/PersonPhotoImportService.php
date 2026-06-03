<?php

namespace App\Services\PhotoArchive;

use App\Models\Person;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PersonPhotoImportService
{
    /**
     * Импортирует одно фото из архива.
     *
     * @param string $filename    Имя файла формате "Фамилимя_Имя_Отчество_1.jpg")
     * @param string $tempFilePath Временный путь к распакованному изображению
     */
    public function importPhoto(string $filename, string $tempFilePath): void
    {

        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('_', $nameWithoutExt);

        $lastPart = end($parts);
        if (is_numeric($lastPart) && count($parts) > 2) {
            array_pop($parts); // убираем суффикс _1, _2 и т.д.
        }

        if (count($parts) < 2) {
            Log::error("Ошибка в имени файла: {$filename}");
            throw new \InvalidArgumentException("Ошибка в имени файла: имя файла должно содержать минимум фамилию и имя: {$filename}");
        }

        $lastName = $parts[0] ?? null;
        $firstName = $parts[1] ?? '';
        $middleName = $parts[2] ?? null;

        $person = Person::firstOrCreate(
            [
                'last_name'   => $lastName,
                'first_name'  => $firstName,
                'middle_name' => $middleName,
            ]
        );

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $storagePath = 'person/photos/' . Str::uuid() . '.' . $extension;

        Storage::disk('public')->putFileAs(
            dirname($storagePath),
            $tempFilePath,
            basename($storagePath)
        );

        $photos = $person->photo ? : [];
        $photos[] = $storagePath;
        $person->photo = array_values(array_unique($photos));
        $person->save();
    }
}
