<?php

namespace Tests\Support;

use GrapesLabs\PinvideoSkud\Contracts\SkudControllerInterface;
use GrapesLabs\PinvideoSkud\Models\SkudController;

class FakeSkudDriver implements SkudControllerInterface
{
    public static array $calls = [];

    public function __construct(private readonly SkudController $controller)
    {
    }

    public function writeKeys(array $keys): bool
    {
        self::$calls[] = ['action' => 'write', 'controller_id' => $this->controller->id, 'keys' => $keys];

        return true;
    }

    public function clearKeys(array $keys): bool
    {
        self::$calls[] = ['action' => 'clear', 'controller_id' => $this->controller->id, 'keys' => $keys];

        return true;
    }

    public function getSerialNumber(): string
    {
        return (string) $this->controller->serial_number;
    }

    public function getType(): string
    {
        return (string) $this->controller->type;
    }

    public function checkAccess($key): bool
    {
        return true;
    }

    public static function reset(): void
    {
        self::$calls = [];
    }

    public static function callsFor(int $controllerId): array
    {
        return array_values(array_filter(self::$calls, fn (array $c) => $c['controller_id'] === $controllerId));
    }
}
