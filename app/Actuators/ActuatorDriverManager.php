<?php

namespace App\Actuators;

use App\Actuators\Contracts\ActuatorDriver;
use InvalidArgumentException;

class ActuatorDriverManager
{
    private array $drivers = [];

    public function register(string $class): void
    {
        if (! is_subclass_of($class, ActuatorDriver::class)) {
            throw new InvalidArgumentException(
                "Класс [{$class}] не реализует ActuatorDriver."
            );
        }

        $this->drivers[$class::key()] = $class;
    }

    public function keys(): array
    {
        return array_keys($this->drivers);
    }

    public function has(string $key): bool
    {
        return isset($this->drivers[$key]);
    }

    public function driver(string $key): ActuatorDriver
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Драйвер не найден: {$key}");
        }

        return app($this->drivers[$key]);
    }

    public function options(): array
    {
        $options = [];

        foreach ($this->drivers as $key => $class) {
            $options[$key] = $class::title();
        }

        return $options;
    }
}
