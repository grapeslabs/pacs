<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\User;
use App\Models\VideoWall;
use App\MoonShine\Pages\VideoWallPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;

class VideoWallController extends Controller
{
    public const MAX_SCREENS = 36;

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'layout' => ['sometimes', 'array'],
        ]);

        $wall = VideoWall::create([
            'name' => trim($data['name']),
            'layout' => $this->normalizeLayout($data['layout'] ?? []),
            'sort' => (int) VideoWall::max('sort') + 1,
        ]);

        return response()->json($this->present($wall));
    }

    public function update(Request $request, VideoWall $videoWall): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'layout' => ['sometimes', 'array'],
        ]);

        if (array_key_exists('name', $data)) {
            $videoWall->name = trim($data['name']);
        }

        if (array_key_exists('layout', $data)) {
            $videoWall->layout = $this->normalizeLayout($data['layout']);
        }

        $videoWall->save();

        return response()->json($this->present($videoWall));
    }

    public function destroy(VideoWall $videoWall): JsonResponse
    {
        $this->authorizeAccess();

        $videoWall->delete();

        return response()->json(['status' => 'ok']);
    }

    private function present(VideoWall $wall): array
    {
        return [
            'id' => $wall->id,
            'name' => $wall->name,
            'layout' => $wall->layout ?? [],
            'update_url' => route('video-walls.update', $wall),
            'destroy_url' => route('video-walls.destroy', $wall),
        ];
    }

    private function normalizeLayout(array $layout): array
    {
        $known = Stream::pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $used = [];
        $columns = [];

        foreach ($layout as $column) {
            if (!is_array($column) || !is_array($column['items'] ?? null)) {
                continue;
            }

            $items = [];

            foreach ($column['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id = (int) ($item['id'] ?? 0);

                if (!in_array($id, $known, true) || in_array($id, $used, true)) {
                    continue;
                }

                $used[] = $id;
                $items[] = ['id' => $id, 'h' => $this->weight($item['h'] ?? 1)];
            }

            if ($items !== []) {
                $columns[] = ['w' => $this->weight($column['w'] ?? 1), 'items' => $items];
            }
        }

        if (count($used) > self::MAX_SCREENS) {
            throw ValidationException::withMessages([
                'layout' => 'На стену помещается до ' . self::MAX_SCREENS . ' камер',
            ]);
        }

        return $columns;
    }

    private function weight(mixed $value): float
    {
        $weight = is_numeric($value) ? (float) $value : 1.0;

        return round(max(0.3, min(12.0, $weight)), 4);
    }

    private function authorizeAccess(): void
    {
        $user = MoonShineAuth::getGuard()->user();

        abort_if(
            !$user instanceof User || !$user->hasPermission(VideoWallPage::class, Ability::VIEW),
            403
        );
    }
}
