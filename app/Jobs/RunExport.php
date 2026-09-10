<?php

namespace App\Jobs;

use App\Models\ExportTask;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MoonShine\ImportExport\ExportHandler;
use Throwable;

class RunExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(private int $taskId)
    {
    }

    public function handle(): void
    {
        $task = ExportTask::query()->find($this->taskId);

        if (! $task) {
            return;
        }

        $resource = moonshine()->getResources()->findByUri($task->resource_uri);

        if (! $resource) {
            $task->update(['status' => ExportTask::STATUS_FAILED, 'error' => 'Раздел не найден']);

            return;
        }

        $task->update(['status' => ExportTask::STATUS_PROCESSING]);

        ExportHandler::process(
            Storage::disk('exports')->path($task->path),
            $resource,
            $task->query ?? [],
            'exports',
            '/',
            ',',
            [0],
        );

        $task->update(['status' => ExportTask::STATUS_DONE]);

        $user = $task->user_id ? User::find($task->user_id) : null;

        $user?->notify(new SystemNotification(
            'info',
            'Выгрузка «' . $resource->getTitle() . '» готова. {link_0}',
            [['url' => route('export-tasks.download', ['task' => $task->id]), 'label' => 'Скачать']],
        ));
    }

    public function failed(Throwable $e): void
    {
        Log::error('RunExport: выгрузка не сформирована', [
            'task_id' => $this->taskId,
            'error' => $e->getMessage(),
        ]);

        ExportTask::query()->where('id', $this->taskId)->update([
            'status' => ExportTask::STATUS_FAILED,
            'error' => $e->getMessage(),
        ]);
    }
}
