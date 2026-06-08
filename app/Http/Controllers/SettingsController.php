<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Stream;
use Illuminate\Http\Request;
use MoonShine\Support\Enums\FlashType;

class SettingsController extends Controller
{
    public function store(Request $request)
    {
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

        session()->flash('toast', [
            'type' => FlashType::SUCCESS->value,
            'message' => 'Настройки успешно сохранены!',
        ]);

        return back();
    }
}
