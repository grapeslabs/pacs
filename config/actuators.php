<?php

use App\Actuators\Drivers\DingtianRelayDriver;
use App\Actuators\Drivers\HttpDriver;
use App\Actuators\Drivers\ModbusTcpDriver;
use App\Actuators\Drivers\SkudControllerDriver;

return [
    'drivers' => [
        ModbusTcpDriver::class,
        DingtianRelayDriver::class,
        HttpDriver::class,
        SkudControllerDriver::class,
    ],
];
