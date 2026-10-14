<?php

namespace App\Http\Controllers;

use App\Models\ExportTask;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportDownloadController extends Controller
{
    public function __invoke(string $file): BinaryFileResponse
    {
        $name = basename($file);

        abort_unless(preg_match('/^[A-Za-z0-9._-]+\.(csv|xlsx)$/', $name) === 1, 404);

        $task = ExportTask::query()->where('path', $name)->first();

        if ($task) {
            $user = MoonShineAuth::getGuard()->user();
            abort_unless($user instanceof User, 403);
            abort_unless($task->user_id === $user->getKey(), 403);

            $resource = moonshine()->getResources()->findByUri($task->resource_uri);
            abort_unless($resource && $resource->can(Ability::VIEW_ANY), 403);
        }

        $disk = Storage::disk('exports');

        abort_unless($disk->exists($name), 404);

        return response()->download($disk->path($name))->deleteFileAfterSend(true);
    }
}
