<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use GrapesLabs\PinvideoSkud\Controllers\PinTermAdapter\OutputPacketProcessor;

class SkudCommand extends Model
{
    protected $table = 'grapeslabs_skud_commands';
    protected $fillable = ['controller_id', 'message', 'execute_at'];

    // Прямой вызов методов контроллера
    // В модели SkudCommand
public static function addPersonDirect($controllerSn, $personUid, $personName)
{
    \Log::info('addPersonDirect called', [
        'controller_sn' => $controllerSn,
        'person_uid' => $personUid,
        'person_name' => $personName
    ]);

    // Находим контроллер
    $controller = \GrapesLabs\PinvideoSkud\Models\SkudController::where('serial_number', $controllerSn)->first();

    if (!$controller) {
        \Log::error('Controller not found', ['sn' => $controllerSn]);
        throw new \Exception("Controller with SN {$controllerSn} not found");
    }

    \Log::info('Controller found', ['controller_id' => $controller->id]);

    // Создаем FaceId ключ
    $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
        uid: $personUid,
        images: [],
        name: $personName
    );

    \Log::info('FaceIdKey created', ['uid' => $personUid]);

    // Создаем экземпляр контроллера
    $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
    \Log::info('Controller instance created');

    // Прямой вызов метода записи ключа
    $result = $skudController->writeKeys([$key]);
    \Log::info('writeKeys result', ['result' => $result]);

    return $result;
}

    public static function deletePersonDirect($controllerSn, $personUid)
    {
        $controller = \GrapesLabs\PinvideoSkud\Models\SkudController::where('serial_number', $controllerSn)->first();

        if (!$controller) {
            throw new \Exception("Controller with SN {$controllerSn} not found");
        }

        $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
            uid: $personUid,
            images: [],
            name: ''
        );

        $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
        $result = $skudController->clearKeys([$key]);

        return $result;
    }
}
