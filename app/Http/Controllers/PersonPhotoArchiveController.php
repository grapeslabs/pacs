<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPersonImportPhotoArchive;
use Illuminate\Http\Request;
use App\Services\PhotoArchive\AllPersonPhotosExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonPhotoArchiveController
{
    public function import(Request $request)
    {
        $request->validate([
            'archive' => 'required|file|mimes:zip|max:51200',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('archive');
        $path = $file->storeAs('temp/imports', uniqid() . '.zip');

        ProcessPersonImportPhotoArchive::dispatch($path);

        return back()->with('alert', 'Архив принят в обработку. Фотографии появятся у персон в течение нескольких минут.');
    }

    public function exportAll(AllPersonPhotosExportService $exportService)
    {
        $zipPath = $exportService->createArchive();

        if (!$zipPath || !file_exists($zipPath)) {
            return back()->with('alert', 'Не удалось создать архив. Возможно, у персон нет фотографий.');
        }

        return new StreamedResponse(function () use ($zipPath) {
            $handle = fopen($zipPath, 'rb');
            fpassthru($handle);
            fclose($handle);
            @unlink($zipPath);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . basename($zipPath) . '"',
            'Content-Length' => filesize($zipPath),
        ]);
    }


}
