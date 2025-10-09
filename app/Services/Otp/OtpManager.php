<?php

declare(strict_types=1);

namespace App\Services\Otp;

use InvalidArgumentException;

class OtpManager
{
    private array $drivers = [];

    public function __construct(private array $config)
    {
    }

    public function driver(string $name = ''): Otp
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] ??= $this->resolve($name);
    }

    public function resolve(string $name): Otp
    {
        $config = $this->config['connections'][$name];

        if (is_null($config)) {
            throw new InvalidArgumentException("OTP connection [{$name}] is not defined.");
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    private function createTruthyDriver(array $config): Otp
    {
        return new TruthyDriver();
    }

    private function createFalsyDriver(array $config): Otp
    {
        return new FalsyDriver();
    }

    private function createGrapesFlashcallsDriver(array $config): Otp
    {
        return new GrapesFlashcalls($config['url'], $config['token']);
    }

    public function getDefaultDriver(): string
    {
        return $this->config['default'];
    }
}
