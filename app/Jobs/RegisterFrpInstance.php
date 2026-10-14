<?php

namespace App\Jobs;

use App\Services\FrpService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterFrpInstance implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $backoff = 300;

    public int $uniqueFor = 86400;

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(FrpService $frp): void
    {
        if ($frp->isRegistered()) {
            return;
        }

        $frp->register();
    }

    public function failed(Throwable $e): void
    {
        Log::warning('FRP: instance registration gave up', ['error' => $e->getMessage()]);
    }
}
