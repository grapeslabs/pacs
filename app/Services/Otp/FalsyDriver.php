<?php

declare(strict_types=1);

namespace App\Services\Otp;

class FalsyDriver implements Otp
{
    public function send(ClientId $clientId): void
    {
    }

    public function check(ClientId $clientId, Password $password): bool
    {
        return false;
    }
}
