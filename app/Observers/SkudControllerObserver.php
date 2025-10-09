<?php

namespace App\Observers;

use GrapesLabs\PinvideoSkud\Models\SkudController;
use App\Models\Car;
use Illuminate\Support\Facades\Log;

class SkudControllerObserver
{
    /**
     * Обработка после создания контроллера
     */
    public function created(SkudController $controller): void
    {
        // Если это Pingate контроллер - отправляем все автомобильные номера
        if (strtolower($controller->type) === 'pingate') {
            $this->sendPowerOnController($controller);
            $this->sendAllCarsToController($controller);
        }
    }


    protected function sendPowerOnController(SkudController $controller): void
    {
        // Отправляем на контроллер
        $result = \GrapesLabs\PinvideoSkud\Controllers\PinGateController\PingateOutputPacketProcessor::set_active(
            $controller->id
        );
    }


    /**
     * Отправка всех автомобильных номеров на контроллер
     */
    protected function sendAllCarsToController(SkudController $controller): void
    {
        try {
            // Получаем все уникальные номера автомобилей
            $licensePlates = Car::whereNotNull('license_plate')
                ->where('license_plate', '!=', '')
                ->pluck('license_plate')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            if (empty($licensePlates)) {
                Log::info('No cars found to send to new Pingate controller', [
                    'controller_id' => $controller->id,
                    'serial_number' => $controller->serial_number,
                ]);
                return;
            }

            Log::info('Sending all cars to new Pingate controller', [
                'controller_id' => $controller->id,
                'serial_number' => $controller->serial_number,
                'cars_count' => count($licensePlates),
                'cars_sample' => array_slice($licensePlates, 0, 5),
            ]);

            // Отправляем на контроллер
            $result = \GrapesLabs\PinvideoSkud\Controllers\PinGateController\PingateOutputPacketProcessor::add_cars(
                $controller->id,
                $licensePlates
            );

            Log::info('Send all cars result', [
                'controller_id' => $controller->id,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending cars to new Pingate controller', [
                'controller_id' => $controller->id,
                'serial_number' => $controller->serial_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка перед удалением контроллера
     */
    public function deleting(SkudController $controller): void
    {
        if (strtolower($controller->type) === 'pingate') {
            try {
                \GrapesLabs\PinvideoSkud\Controllers\PinGateController\PingateOutputPacketProcessor::clear_cars($controller->id);
                Log::info('Cleared cars from deleted Pingate controller', [
                    'controller_id' => $controller->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Error clearing cars from deleted Pingate controller', [
                    'controller_id' => $controller->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
