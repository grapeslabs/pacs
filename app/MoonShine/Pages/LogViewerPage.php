<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Components\LogViewer;
use App\Services\LogReaderService;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Pages\Page;
use Throwable;

class LogViewerPage extends Page
{
    protected string $title = 'Журнал логов';

    protected ?array $breadcrumbs = ['Журнал логов'];

    protected function components(): iterable
    {
        abort_if(
            !auth()->user()?->hasPermission(self::class, Ability::VIEW),
            403
        );

        $reader = app(LogReaderService::class);

        $sources = $reader->sources();
        $source = request()->input('source');

        if (!array_key_exists((string) $source, $sources)) {
            $source = $reader->defaultSource();
        }

        $levels = array_values(array_intersect(
            (array) request()->input('levels', []),
            array_keys(LogReaderService::LEVELS)
        ));

        $search = trim((string) request()->input('search', ''));
        $page = max(1, (int) request()->input('page', 1));

        $result = [
            'entries' => [],
            'has_more' => false,
            'truncated' => false,
            'size' => 0,
            'files' => 0,
            'newest' => null,
            'oldest' => null,
        ];

        if ($source !== null) {
            try {
                $result = $reader->entries($source, [
                    'levels' => $levels,
                    'search' => $search,
                ], $page);
            } catch (Throwable $e) {}
        }

        return [
            LogViewer::make(
                sources: $sources,
                levels: LogReaderService::LEVELS,
                entries: $result['entries'],
                state: [
                    'source' => $source,
                    'search' => $search,
                    'selected_levels' => $levels,
                    'page' => $page,
                    'has_more' => $result['has_more'],
                    'truncated' => $result['truncated'],
                    'size' => $result['size'],
                    'files' => $result['files'],
                    'newest' => $result['newest'],
                    'oldest' => $result['oldest'],
                    'writable' => $source !== null && ($sources[$source]['writable'] ?? false),
                    'page_url' => $this->getUrl(),
                    'download_url' => route('logs.download', ['source' => $source]),
                    'clear_url' => route('logs.clear'),
                ]
            ),
        ];
    }
}
