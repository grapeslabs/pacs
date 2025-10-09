<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use GrapesLabs\PinvideoSkud\Http\Controllers\SkudApiController;
use App\Http\Controllers\Api\V1\OrganizationApiController;
use App\Http\Controllers\Api\V1\GuestApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('skud')->group(function () {
    Route::post('/controller/message', [SkudApiController::class, 'handleControllerMessage']);
    Route::post('/keys/write', [SkudApiController::class, 'writeKey']);
    Route::post('/keys/clear', [SkudApiController::class, 'clearKey']);
});

Route::prefix('v1')->group(function () {
    Route::get('/organizations', [OrganizationApiController::class, 'index']);
    Route::post('/guests/auth', [GuestApiController::class, 'auth']);
    Route::post('/guests/confirm', [GuestApiController::class, 'confirm']);
    Route::post('/guests/{id}/photos', [GuestApiController::class, 'storePhotos']);
});

// routes/web.php или routes/api.php
Route::get('/test-skud-integration', function() {
    try {
        $person = \App\Models\Person::first();
        if (!$person) {
            return "No persons found in database";
        }

        \Log::info("🧪 TEST SKUD Integration for person: " . $person->id);

        $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::all();
        \Log::info("🧪 Found controllers: " . $controllers->count());

        // Тестируем создание ключа
        $images = [];
        if (!empty($person->photo)) {
            foreach ($person->photo as $photoPath) {
                $fullPath = Storage::disk('public')->path($photoPath);
                if (file_exists($fullPath)) {
                    $imageContent = file_get_contents($fullPath);
                    $images[] = [
                        'id' => pathinfo($photoPath, PATHINFO_FILENAME),
                        'content' => $imageContent
                    ];
                }
            }
        }

        \Log::info("🧪 Images processed: " . count($images));

        $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
            uid: $person->getSkudUid(),
            images: $images,
            name: $person->getFullName()
        );

        \Log::info("🧪 Key created: " . $person->getSkudUid());

        // Тестируем первый контроллер
        $controller = $controllers->first();
        $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
        $result = $skudController->writeKeys([$key]);

        \Log::info("🧪 SKUD Integration Result", ['result' => $result]);

        return "SKUD Integration Test Completed. Check logs.";

    } catch (\Exception $e) {
        \Log::error("🧪 SKUD Integration Test Failed", ['error' => $e->getMessage()]);
        return "Error: " . $e->getMessage();
    }
});
