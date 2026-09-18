<?php

namespace App\Jobs;

use App\Models\VideoAnalyticReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkReportsIdentified implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300];
    public int $timeout = 30;

    public function __construct(private string $grapesvaUuid)
    {
    }

    public function handle(): void
    {
        if (! config('services.va.enabled')) {
            return;
        }

        VideoAnalyticReport::query()->toBase()
            ->where('person_photobank_id', $this->grapesvaUuid)
            ->where('is_unknown', true)
            ->update(['is_unknown' => false]);
    }
}
