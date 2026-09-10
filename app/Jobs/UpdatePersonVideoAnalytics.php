<?php

namespace App\Jobs;

use App\Models\Person;
use App\Services\VideoAnalyticService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdatePersonVideoAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60];
    public int $timeout = 60;

    public function __construct(private int $personId)
    {
    }

    public function handle(VideoAnalyticService $vas): void
    {
        $person = Person::query()->find($this->personId);

        if (! $person || empty($person->photo)) {
            return;
        }

        $photosFullPaths = [];
        foreach ($person->photo as $photoPath) {
            $fullPath = Storage::disk('public')->path($photoPath);
            if (file_exists($fullPath)) {
                $photosFullPaths[] = $fullPath;
            }
        }

        if (! empty($photosFullPaths)) {
            $vas->personCreate($person->getFullName(), $photosFullPaths, $person->grapesva_uuid);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('UpdatePersonVideoAnalytics: не удалось обновить фото персоны в видеоаналитике', [
            'person_id' => $this->personId,
            'error' => $e->getMessage(),
        ]);
    }
}
