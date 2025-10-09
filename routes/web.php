<?php

use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DadataController;
use App\Models\Person;
use \App\Http\Controllers\ChatsController;
use \App\Http\Controllers\SettingsController;
use \App\Http\Controllers\StreamController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('tags')->group(function() {
    Route::get('list', [TagController::class, 'index'])->name('tags.index');
    Route::post('store', [TagController::class, 'store'])->name('tags.store');
    Route::delete('{tag}', [TagController::class, 'store'])->name('tags.destroy');
});

Route::prefix('settings')->group(function() {
   Route::post('store', [SettingsController::class, 'store'])->name('settings.store');
});

Route::prefix('chats')->group(function () {
   Route::get('list', [ChatsController::class, 'index'])->name('chats.index');
   Route::post('store', [ChatsController::class, 'store'])->name('chats.store');
   Route::get('test', [ChatsController::class, 'test'])->name('chats.test');
});

Route::prefix("/streams")->group(function () {
    Route::post("/archive-download/{stream}", [StreamController::class, 'downloadArchive'])->name("streams.archive_download");
    Route::get("/download-status/{stream}", [StreamController::class, 'downloadStatus'])->name("streams.download_status");
    Route::get("/download-file/{stream}", [StreamController::class, 'downloadArchiveFile'])->name("streams.download_file");
});

Route::get('/organizations/search-dadata', [DadataController::class, 'searchOrganizations'])
    ->name('organizations.search-dadata');

Route::post('/organizations/save-direct', [DadataController::class, 'saveDirect'])
    ->name('organizations.save-direct')
    ->middleware('web');
Route::get('/test-clean-data', [DadataController::class, 'testCleanData']);

Route::post("/test-multipart-direct", function (Illuminate\Http\Request $request) {
    return response()->json([
        "status" => "direct_route_works",
        "content_type" => $request->header("Content-Type"),
        "is_multipart" => str_contains($request->header("Content-Type"), "multipart")
    ]);
});



Route::get('/test-person-skud', function() {
    \Log::info("🧪 TEST Person SKUD Integration Started");

    // Проверим существующие персоны
    $person = Person::first();
    if (!$person) {
        return "No persons found. Please create a person first.";
    }

    \Log::info("🧪 Testing with person: " . $person->id . " - " . $person->getFullName());

    // Проверим методы модели
    \Log::info("🧪 Person methods:", [
        'skud_uid' => $person->getSkudUid(),
        'full_name' => $person->getFullName(),
        'photos_count' => count($person->photo ?? [])
    ]);

    return response()->json([
        'person_id' => $person->id,
        'skud_uid' => $person->getSkudUid(),
        'full_name' => $person->getFullName(),
        'photos' => $person->photo ?? []
    ]);
});
