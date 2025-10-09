<?php

declare(strict_types=1);

namespace App\Services\Otp;

class ClientId
{
    public function __construct(public readonly string $clientId)
    {
    }
}
