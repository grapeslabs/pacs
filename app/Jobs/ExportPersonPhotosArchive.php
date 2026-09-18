<?php

namespace App\Jobs;

use App\Models\ExportTask;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\PhotoArchive\AllPersonPhotosExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportPersonPhotosArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(private int $taskId)
    {
    }

    public function handle(AllPersonPhotosExportService $exportService): void
    {
        $task = ExportTask::query()->find($this->taskId);

        if (! $task) {
            return;
        }

        $task->update(['status' => ExportTask::STATUS_PROCESSING]);

        $zipPath = $exportService->createArchive();

        if (! $zipPath || ! file_exists($zipPath)) {
            $task->update([
                'status' => ExportTask::STATUS_FAILED,
                'error' => 'У персон нет фотографий.',
            ]);

            return;
        }

        rename($zipPath, Storage::disk('exports')->path($task->path));

        $task->update(['status' => ExportTask::STATUS_DONE]);

        $user = $task->user_id ? User::find($task->user_id) : null;

        $user?->notify(new SystemNotification(
            'info',
            'Архив фотографий персон готов. {link_0}',
            [['url' => route('export-tasks.download', ['task' => $task->id]), 'label' => 'Скачать']],
        ));
    }

    public function failed(Throwable $e): void
    {
        Log::error('ExportPersonPhotosArchive: не удалось сформировать архив фотографий персон', [
            'task_id' => $this->taskId,
            'error' => $e->getMessage(),
        ]);

        ExportTask::query()->where('id', $this->taskId)->update([
            'status' => ExportTask::STATUS_FAILED,
            'error' => $e->getMessage(),
        ]);
    }
}
