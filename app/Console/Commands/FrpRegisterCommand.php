<?php

namespace App\Console\Commands;

use App\Jobs\RegisterFrpInstance;
use App\Services\FrpService;
use Illuminate\Console\Command;

class FrpRegisterCommand extends Command
{
    protected $signature = 'frp:register';
    protected $description = 'Queue FRP instance registration (the job retries every 5 minutes for a day)';

    public function handle(FrpService $frp): int
    {
        if ($frp->isRegistered()) {
            $this->info('FRP instance already registered.');

            return self::SUCCESS;
        }

        RegisterFrpInstance::dispatch();
        $this->info('FRP instance registration queued.');

        return self::SUCCESS;
    }
}
