<?php

namespace App\Http\Controllers;

use App\Jobs\ExportPersonPhotosArchive;
use App\Jobs\ProcessPersonImportPhotoArchive;
use App\Models\ExportTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MoonShine\Laravel\MoonShineAuth;

class PersonPhotoArchiveController
{
    public function import(Request $request)
    {
        $request->validate([
            'archive' => 'required|file|mimes:zip|max:512000',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('archive');
        $path = $file->storeAs('temp/imports', uniqid() . '.zip');

        ProcessPersonImportPhotoArchive::dispatch($path, MoonShineAuth::getGuard()->id());

        return back()->with('moonshine_custom_toast', ['message' => 'Архив принят в обработку. Фотографии появятся у персон в течение нескольких минут.']);
    }

    public function exportAll(): JsonResponse
    {
        $task = ExportTask::create([
            'user_id' => MoonShineAuth::getGuard()->id(),
            'resource_uri' => 'person-photos',
            'format' => ExportTask::FORMAT_ZIP,
            'status' => ExportTask::STATUS_PENDING,
            'path' => Str::uuid() . '.zip',
        ]);

        ExportPersonPhotosArchive::dispatch($task->id);

        return response()->json([
            'task_id' => $task->id,
            'status_url' => route('export-tasks.status', ['task' => $task->id]),
            'download_url' => route('export-tasks.download', ['task' => $task->id]),
        ]);
    }
}
