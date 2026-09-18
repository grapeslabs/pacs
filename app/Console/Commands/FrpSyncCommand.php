<?php

namespace App\Console\Commands;

use App\Services\FrpService;
use Illuminate\Console\Command;
use Throwable;

class FrpSyncCommand extends Command
{
    protected $signature = 'frp:sync';
    protected $description = 'Restore the frpc tunnel if the setting is on but the service is not running';

    public function handle(FrpService $frp): int
    {
        if (!$frp->isEnabled()) {
            return self::SUCCESS;
        }

        try {
            $frp->sync();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
