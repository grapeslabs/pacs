<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Http\Controllers\VideoWallController;
use App\Models\Stream;
use App\Models\User;
use App\Models\VideoWall;
use App\MoonShine\Components\VideoWallComponent;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\Page;

class VideoWallPage extends Page
{
    protected string $title = 'Видеостены';

    protected ?array $breadcrumbs = ['Видеостены'];

    protected function components(): iterable
    {
        $user = MoonShineAuth::getGuard()->user();

        abort_if(
            !$user instanceof User || !$user->hasPermission(self::class, Ability::VIEW),
            403
        );

        $known = Stream::pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $walls = VideoWall::orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(static fn (VideoWall $wall): array => [
                'id' => $wall->id,
                'name' => $wall->name,
                'layout' => array_values(array_filter(
                    array_map(static fn (array $column): array => [
                        'w' => $column['w'] ?? 1,
                        'items' => array_values(array_filter(
                            $column['items'] ?? [],
                            static fn ($item): bool => is_array($item) && in_array((int) ($item['id'] ?? 0), $known, true)
                        )),
                    ], $wall->layout ?? []),
                    static fn (array $column): bool => $column['items'] !== []
                )),
                'update_url' => route('video-walls.update', $wall),
                'destroy_url' => route('video-walls.destroy', $wall),
            ])
            ->all();

        $streams = Stream::orderBy('name')
            ->get()
            ->map(static fn (Stream $stream): array => [
                'id' => $stream->id,
                'uid' => $stream->uid,
                'name' => $stream->name,
                'location' => $stream->location,
                'is_active' => (bool) $stream->is_active,
                'player_url' => route('moonshine.resource.page', [
                    'video-stream-resource',
                    'stream-player',
                    $stream->id,
                ]),
            ])
            ->all();

        return [
            VideoWallComponent::make(
                walls: $walls,
                streams: $streams,
                state: [
                    'store_url' => route('video-walls.store'),
                    'max_screens' => VideoWallController::MAX_SCREENS,
                ]
            ),
        ];
    }
}
