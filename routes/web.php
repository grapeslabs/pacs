<?php

use App\Http\Controllers\CarTagController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DadataController;
use App\Models\Person;
use App\Http\Controllers\ChatsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DetailCardController;
use App\Http\Controllers\DetailModalController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\PersonPhotoArchiveController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\SearchController;
use MoonShine\Laravel\Http\Middleware\Authenticate as MoonShineAuthenticate;
use App\Http\Controllers\PresenceReportController;


Route::prefix(config('moonshine.prefix', 'admin'))
    ->middleware(\MoonShine\Laravel\Http\Middleware\Authenticate::class)
    ->group(function () {
        Route::get('detail-card/{resource}/{id}', [DetailCardController::class, 'show'])
            ->name('detail-card');
        Route::get('detail-modal/{resource}/{id}', [DetailModalController::class, 'show'])
            ->name('detail-modal');
    });


Route::prefix('tags')->group(function() {
    Route::get('list', [TagController::class, 'index'])->name('tags.index');
    Route::post('store', [TagController::class, 'store'])->name('tags.store');
    Route::delete('{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});

Route::prefix('car-tags')->group(function() {
    Route::get('list', [CarTagController::class, 'index'])->name('car-tags.index');
    Route::post('store', [CarTagController::class, 'store'])->name('car-tags.store');
    Route::delete('{carTag}', [CarTagController::class, 'destroy'])->name('car-tags.destroy');
});

Route::post("/field-validation", [CustomFieldController::class, "validate"])->name("field.validation");

Route::get("/system-search", [SearchController::class, "index"])->name("system.search");

Route::prefix("settings")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::post("store", [SettingsController::class, "store"])->name("settings.store");
    });

Route::prefix("presence-report")->group(function () {
    Route::post("generate", [PresenceReportController::class, "generate"])->name("presence-report.generate");
    Route::get("status/{report}", [PresenceReportController::class, "status"])->name("presence-report.status");
    Route::get("export/{report}", [PresenceReportController::class, "export"])->name("presence-report.export");
});

Route::prefix("logs")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::get("download", [LogViewerController::class, "download"])->name("logs.download");
        Route::post("clear", [LogViewerController::class, "clear"])->name("logs.clear");
    });

Route::prefix("chats")->group(function () {
    Route::get("list", [ChatsController::class, "index"])->name("chats.index");
    Route::post("store", [ChatsController::class, "store"])->name("chats.store");
    Route::get("test", [ChatsController::class, "test"])->name("chats.test");
});

Route::prefix("/streams")->group(function () {
    Route::post("/archive-download/{stream}", [StreamController::class, "downloadArchive"])->name(
        "streams.archive_download",
    );
    Route::get("/download-status/{stream}", [StreamController::class, "downloadStatus"])->name(
        "streams.download_status",
    );
    Route::get("/download-file/{stream}", [StreamController::class, "downloadArchiveFile"])->name(
        "streams.download_file",
    );
});

Route::get("/organizations/search-dadata", [DadataController::class, "searchOrganizations"])->name(
    "organizations.search-dadata",
);

Route::post("/organizations/save-direct", [DadataController::class, "saveDirect"])
    ->name("organizations.save-direct")
    ->middleware("web");

Route::post("/person-photos/import", [PersonPhotoArchiveController::class, "import"])->name("person-photos.import");
Route::get("/person-photos/export-all", [PersonPhotoArchiveController::class, "exportAll"])->name(
    "person-photos.export-all",
);

Route::post("/profile-settings/personal", [ProfileSettingsController::class, "personal"])->name("profile-settings.personal");
Route::post("/profile-settings/password", [ProfileSettingsController::class, "password"])->name("profile-settings.password");
Route::post("/profile-settings/avatar", [ProfileSettingsController::class, "avatar"])->name("profile-settings.avatar");
Route::post("/profile-settings/avatar/delete", [ProfileSettingsController::class, "avatarDelete"])->name("profile-settings.avatar-delete");

