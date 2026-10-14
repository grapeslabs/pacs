<?php

use Illuminate\Support\Facades\Route;
use GrapesLabs\PinvideoSkud\Http\Controllers\SkudApiController;
use App\Http\Controllers\Api\V1\OrganizationApiController;
use App\Http\Controllers\Api\V1\GuestApiController;
use App\Http\Middleware\VerifyApiKey;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\PersonController;
use \App\Http\Controllers\Api\V1\KeyController;

Route::prefix('skud')->group(function () {
    Route::post('/controller/message', [SkudApiController::class, 'handleControllerMessage']);

    Route::middleware(VerifyApiKey::class)->group(function () {
        Route::post('/keys/write', [SkudApiController::class, 'writeKey']);
        Route::post('/keys/clear', [SkudApiController::class, 'clearKey']);
        Route::get('/controllers', [SkudApiController::class, 'getControllers']);
        Route::get('/controllers/{controllerId}/events', [SkudApiController::class, 'getControllerEvents']);
    });
});

Route::prefix('v1')->group(function () {
    Route::get('/organizations', [OrganizationApiController::class, 'index']);
    Route::post('/guests/auth', [GuestApiController::class, 'auth']);
    Route::post('/guests/confirm', [GuestApiController::class, 'confirm']);
    Route::post('/guests/{id}/photos', [GuestApiController::class, 'storePhotos']);
    Route::middleware(VerifyApiKey::class)->group(function() {
        Route::prefix('reports')->group(function () {
            Route::get('events', [ReportController::class, 'events']);
            Route::get('people', [ReportController::class, 'people']);
            Route::get('unknown', [ReportController::class, 'unknown']);
            Route::post('unknown/{report_id}/identify', [ReportController::class, 'identify']);
        });
        Route::prefix('persons')->group(function () {
            Route::get('/', [PersonController::class, 'index']);
            Route::post('/', [PersonController::class, 'store']);
            Route::get('/{id}', [PersonController::class, 'show']);
            Route::post('/{id}', [PersonController::class, 'update']);
            Route::delete('/{id}', [PersonController::class, 'destroy']);
        });

        Route::prefix('keys')->group(function () {
            Route::get('/', [KeyController::class, 'index']);
            Route::post('/', [KeyController::class, 'store']);
            Route::get('/{keyItem}', [KeyController::class, 'show']);
            Route::post('/{keyItem}', [KeyController::class, 'update']);
            Route::delete('/{keyItem}', [KeyController::class, 'destroy']);
        });
    });
});
