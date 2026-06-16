<?php

namespace App\Actuators\Contracts;

use App\Models\ActuatorDevice;

interface ActuatorDriver
{
    public static function key(): string;
    public static function title(): string;
    public function fields(): array;
    public function rules(): array;
    public function open(ActuatorDevice $device): void;
    public function close(ActuatorDevice $device): void;
    public function test(ActuatorDevice $device): bool;
    public function capabilities(): array;
}
