<?php

use App\Http\Controllers\CarTagController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DadataController;
use App\Models\Person;
use App\Http\Controllers\ChatsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\VideoWallController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DetailCardController;
use App\Http\Controllers\DetailModalController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\PersonPhotoArchiveController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\ExportTaskController;
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


Route::middleware(MoonShineAuthenticate::class)->group(function () {
    Route::post('tags/store', [TagController::class, 'store'])->name('tags.store');
    Route::post('car-tags/store', [CarTagController::class, 'store'])->name('car-tags.store');
});

Route::post("/field-validation", [CustomFieldController::class, "validate"])
    ->middleware([MoonShineAuthenticate::class, "throttle:60,1"])
    ->name("field.validation");

Route::get("/system-search", [SearchController::class, "index"])->name("system.search");

Route::get("/exports/{file}", ExportDownloadController::class)
    ->middleware(MoonShineAuthenticate::class)
    ->name("exports.download");

Route::prefix("export-tasks")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::post("/", [ExportTaskController::class, "enqueue"])
            ->middleware("throttle:10,1")
            ->name("export-tasks.enqueue");
        Route::get("{task}/status", [ExportTaskController::class, "status"])->name("export-tasks.status");
        Route::get("{task}/download", [ExportTaskController::class, "download"])->name("export-tasks.download");
    });

Route::prefix("settings")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::post("store", [SettingsController::class, "store"])->name("settings.store");
        Route::post("frp/rotate-token", [SettingsController::class, "rotateFrpToken"])
            ->name("settings.frp.rotate-token");
    });

if (config('services.va.enabled')) {
    Route::prefix("presence-report")
        ->middleware(MoonShineAuthenticate::class)
        ->group(function () {
            Route::post("generate", [PresenceReportController::class, "generate"])
                ->middleware("throttle:5,1")
                ->name("presence-report.generate");
            Route::get("status/{report}", [PresenceReportController::class, "status"])->name("presence-report.status");
            Route::get("export/{report}", [PresenceReportController::class, "export"])->name("presence-report.export");
        });
}

Route::prefix("logs")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::get("download", [LogViewerController::class, "download"])->name("logs.download");
        Route::post("clear", [LogViewerController::class, "clear"])->name("logs.clear");
    });

Route::prefix("chats")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::get("test", [ChatsController::class, "test"])->name("chats.test");
    });

Route::prefix("video-walls")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::post("store", [VideoWallController::class, "store"])->name("video-walls.store");
        Route::post("{videoWall}", [VideoWallController::class, "update"])->name("video-walls.update");
        Route::delete("{videoWall}", [VideoWallController::class, "destroy"])->name("video-walls.destroy");
    });

Route::prefix("/streams")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
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

Route::get("/organizations/search-dadata", [DadataController::class, "searchOrganizations"])
    ->middleware([MoonShineAuthenticate::class, "throttle:30,1"])
    ->name("organizations.search-dadata");

Route::prefix("person-photos")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::post("import", [PersonPhotoArchiveController::class, "import"])->name("person-photos.import");
        Route::post("export-tasks", [PersonPhotoArchiveController::class, "exportAll"])
            ->middleware("throttle:3,1")
            ->name("person-photos.export-all");
    });

Route::prefix("profile-settings")
    ->middleware(MoonShineAuthenticate::class)
    ->group(function () {
        Route::post("personal", [ProfileSettingsController::class, "personal"])->name("profile-settings.personal");
        Route::post("password", [ProfileSettingsController::class, "password"])->name("profile-settings.password");
        Route::post("avatar", [ProfileSettingsController::class, "avatar"])->name("profile-settings.avatar");
        Route::post("avatar/delete", [ProfileSettingsController::class, "avatarDelete"])->name("profile-settings.avatar-delete");
    });

