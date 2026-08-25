<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\MoonShine\Pages\LogViewerPage;
use App\Services\LogReaderService;
use Illuminate\Http\Request;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Support\Enums\FlashType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LogViewerController extends Controller
{
    public function __construct(private readonly LogReaderService $reader)
    {
    }

    public function download(Request $request): StreamedResponse
    {
        $this->authorizeAccess();

        $source = (string) $request->input('source');

        try {
            $paths = $this->reader->paths($source);
        } catch (Throwable $e) {
            abort(404, $e->getMessage());
        }

        abort_if($paths === [], 404, 'В этом источнике нет логов');

        return response()->streamDownload(
            static function () use ($paths): void {
                foreach ($paths as $path) {
                    $handle = @fopen($path, 'rb');

                    if ($handle === false) {
                        continue;
                    }

                    fpassthru($handle);
                    fclose($handle);
                }
            },
            $source . '-' . now()->format('Y-m-d') . '.log',
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    public function clear(Request $request)
    {
        $this->authorizeAccess();

        try {
            $cleared = $this->reader->clear((string) $request->input('source'));

            session()->flash('toast', [
                'type' => FlashType::SUCCESS->value,
                'message' => "Логи источника очищены (файлов: {$cleared})",
            ]);
        } catch (Throwable $e) {
            session()->flash('toast', [
                'type' => FlashType::ERROR->value,
                'message' => $e->getMessage(),
            ]);
        }

        return back();
    }

    private function authorizeAccess(): void
    {
        $user = MoonShineAuth::getGuard()->user();

        abort_if(
            !$user instanceof User || !$user->hasPermission(LogViewerPage::class, Ability::VIEW),
            403
        );
    }
}
