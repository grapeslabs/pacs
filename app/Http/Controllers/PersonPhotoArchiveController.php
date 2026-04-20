<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPersonImportPhotoArchive;
use Illuminate\Http\Request;

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
}
