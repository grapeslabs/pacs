<?php

declare(strict_types=1);

namespace App\Services\Otp;

class Password
{
    public function __construct(public readonly string $password)
    {
    }
}
