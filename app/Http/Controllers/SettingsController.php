<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Stream;
use App\Models\User;
use App\MoonShine\Pages\SettingsPage;
use App\Services\FrpService;
use Illuminate\Http\Request;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Support\Enums\FlashType;
use MoonShine\Support\Enums\ToastType;

class SettingsController extends Controller
{
    public function store(Request $request, FrpService $frp)
    {
        $user = MoonShineAuth::getGuard()->user();

        abort_if(
            !$user instanceof User || !$user->hasPermission(SettingsPage::class, Ability::VIEW),
            403
        );

        $settings = [
            'face_recognition' => $request->boolean('face_recognition', false),
            'stream_autoresume' => $request->boolean('stream_autoresume', true),
            'drive_limit' => $request->input('drive_limit', 100),
        ];
        $streams = Stream::all();

         if (!$settings['face_recognition']) {
            foreach ($streams as $stream) {
                $stream->update([
                        'va_options->global_enable' => false,
                        'va_options->is_face_detection' => false,
                        'va_options->is_motion_detection' => false,
                    ]
                );
            }
        }

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['name' => $key, 'value' => $value]
            );
        }

        $frpEnabled = $request->boolean('frp_enabled', false);

        if ($frpEnabled !== $frp->isEnabled()) {
            try {
                $frpEnabled ? $frp->enable() : $frp->disable();
            } catch (\Throwable $e) {
                session()->flash('toast', [
                    'type' => FlashType::ERROR->value,
                    'message' => $e->getMessage(),
                ]);

                return back();
            }
        }

        session()->flash('toast', [
            'type' => FlashType::SUCCESS->value,
            'message' => 'Настройки успешно сохранены!',
        ]);

        return back();
    }

    public function rotateFrpToken(FrpService $frp): MoonShineJsonResponse
    {
        try {
            $token = $frp->rotateToken();
        } catch (\Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast($e->getMessage(), ToastType::ERROR)
                ->setStatusCode(422);
        }

        return MoonShineJsonResponse::make(['token' => $token])
            ->toast('Получен новый токен.', ToastType::SUCCESS);
    }
}
