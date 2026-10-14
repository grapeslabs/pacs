<?php

namespace App\Http\Controllers;

use App\Jobs\RunExport;
use App\Models\ExportTask;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportTaskController extends Controller
{
    public function enqueue(Request $request): JsonResponse
    {
        $user = $this->user();

        $data = $request->validate([
            'resource' => ['required', 'string', 'max:255'],
            'format' => ['required', 'in:' . ExportTask::FORMAT_XLSX . ',' . ExportTask::FORMAT_CSV],
        ]);

        $resource = moonshine()->getResources()->findByUri($data['resource']);

        abort_if($resource === null, 404);
        abort_unless($resource->can(Ability::VIEW_ANY), 403);

        $task = ExportTask::create([
            'user_id' => $user->getKey(),
            'resource_uri' => $data['resource'],
            'format' => $data['format'],
            'status' => ExportTask::STATUS_PENDING,
            'query' => $request->input('query', []),
            'path' => Str::uuid() . '.' . $data['format'],
        ]);

        RunExport::dispatch($task->id);

        return response()->json([
            'task_id' => $task->id,
            'status_url' => route('export-tasks.status', ['task' => $task->id]),
            'download_url' => route('export-tasks.download', ['task' => $task->id]),
        ]);
    }

    public function status(ExportTask $task): JsonResponse
    {
        $this->authorizeTask($task);

        return response()->json([
            'status' => $task->status,
            'error' => $task->status === ExportTask::STATUS_FAILED
                ? ($task->error ?: 'Не удалось сформировать выгрузку')
                : null,
        ]);
    }

    public function download(ExportTask $task): BinaryFileResponse
    {
        $this->authorizeTask($task);

        abort_unless($task->status === ExportTask::STATUS_DONE, 404);

        $disk = Storage::disk('exports');

        abort_unless($task->path && $disk->exists($task->path), 404);

        return response()
            ->download($disk->path($task->path), $this->fileName($task))
            ->deleteFileAfterSend(true);
    }

    private function user(): User
    {
        $user = MoonShineAuth::getGuard()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function authorizeTask(ExportTask $task): void
    {
        $user = $this->user();

        abort_unless($task->user_id === $user->getKey(), 403);
    }

    private function fileName(ExportTask $task): string
    {
        return $task->resource_uri . '-' . $task->created_at->format('Y-m-d-His') . '.' . $task->format;
    }
}
