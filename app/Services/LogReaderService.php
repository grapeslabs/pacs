<?php

namespace App\Services;

use Generator;
use RuntimeException;

class LogReaderService
{
    public const LEVELS = [
        'debug' => 'Отладка',
        'info' => 'Информация',
        'notice' => 'Уведомление',
        'warning' => 'Предупреждение',
        'error' => 'Ошибка',
        'critical' => 'Критическая',
        'alert' => 'Тревога',
        'emergency' => 'Авария',
    ];

    private const LEVEL_ALIASES = [
        'warn' => 'warning',
        'err' => 'error',
        'fatal' => 'critical',
        'crit' => 'critical',
        'emerg' => 'emergency',
        'trace' => 'debug',
        'verbose' => 'debug',
        'fine' => 'debug',
        'severe' => 'error',
        'exception' => 'error',
    ];

    private const LARAVEL_ENTRY = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\]\s*([^\s:]+?)\.([A-Za-z]+):\s?(.*)$/s';

    private const PLAIN_ENTRY = '/^\[?(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:[.,]\d+)?(?:[+-]\d{2}:?\d{2}|Z)?)\]?\s*(.*)$/s';

    private const PLAIN_MODULE = '/^-\s*([^-\s][^-]*?)\s*-\s*([A-Za-z]+)\s*-\s*(.*)$/s';

    private const MAX_ENTRY_LINES = 200;

    public function sources(): array
    {
        $sources = [];

        foreach ((array) config('logs.sources', []) as $key => $source) {
            if (!($source['enabled'] ?? true)) {
                continue;
            }

            $sources[$key] = [
                'key' => $key,
                'label' => $source['label'] ?? $key,
                'path' => $source['path'] ?? null,
                'format' => $source['format'] ?? 'plain',
                'writable' => (bool) ($source['writable'] ?? false),
                'exists' => is_dir($source['path'] ?? ''),
            ];
        }

        return $sources;
    }

    public function source(?string $key): ?array
    {
        return $this->sources()[$key] ?? null;
    }

    public function defaultSource(): ?string
    {
        $sources = $this->sources();

        foreach ($sources as $key => $source) {
            if ($this->files($key) !== []) {
                return $key;
            }
        }

        return array_key_first($sources);
    }

    public function files(string $sourceKey): array
    {
        $source = $this->source($sourceKey);

        if ($source === null || !is_dir($source['path'])) {
            return [];
        }

        $directory = rtrim($source['path'], '/');
        $files = [];

        foreach (scandir($directory) ?: [] as $name) {
            if (!preg_match('/\.(log|log\.\d+|txt)$/i', $name)) {
                continue;
            }

            $path = $directory . '/' . $name;

            if (!is_file($path)) {
                continue;
            }

            $files[] = [
                'name' => $name,
                'size' => filesize($path) ?: 0,
                'modified' => filemtime($path) ?: 0,
            ];
        }

        usort($files, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);

        return $files;
    }

    public function resolvePath(string $sourceKey, string $file): string
    {
        $source = $this->source($sourceKey);

        if ($source === null) {
            throw new RuntimeException('Неизвестный источник логов');
        }

        $directory = realpath($source['path']);

        if ($directory === false) {
            throw new RuntimeException('Каталог логов недоступен');
        }

        $path = realpath($directory . '/' . basename($file));

        if ($path === false || !is_file($path) || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Файл лога не найден');
        }

        return $path;
    }

    public function entries(string $sourceKey, array $filters = [], int $page = 1): array
    {
        $source = $this->source($sourceKey);

        if ($source === null) {
            throw new RuntimeException('Неизвестный источник логов');
        }

        $perPage = (int) config('logs.per_page', 50);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $needed = $offset + $perPage + 1;

        $levels = array_filter((array) ($filters['levels'] ?? []));
        $search = trim((string) ($filters['search'] ?? ''));

        $files = $this->files($sourceKey);
        $size = array_sum(array_column($files, 'size'));
        $budget = (int) config('logs.max_scan_bytes', 64 * 1024 * 1024);

        $entries = [];
        $found = 0;
        $truncated = false;
        $complete = false;

        foreach ($files as $file) {
            if ($budget <= 0) {
                $truncated = true;

                break;
            }

            if ($file['size'] > $budget) {
                $truncated = true;
            }

            $path = $this->resolvePath($sourceKey, $file['name']);

            foreach ($this->rawEntries($path, $budget, $source['format']) as $raw) {
                if ($search !== '' && mb_stripos($raw, $search) === false) {
                    continue;
                }

                $entry = $this->parse($raw, $source['format']);
                $entry['file'] = $file['name'];

                if ($levels !== [] && !in_array($entry['level'], $levels, true)) {
                    continue;
                }

                $found++;

                if ($found > $offset) {
                    $entry['context'] = $this->prettyContext($entry['context']);
                    $entries[] = $entry;
                }

                if ($found >= $needed) {
                    $complete = true;

                    break 2;
                }
            }

            $budget -= $file['size'];
        }

        return [
            'entries' => array_slice($entries, 0, $perPage),
            'has_more' => count($entries) > $perPage,
            'truncated' => $truncated && !$complete,
            'size' => $size,
            'files' => count($files),
            'newest' => $files[0]['modified'] ?? null,
            'oldest' => $files === [] ? null : end($files)['modified'],
        ];
    }

    public function paths(string $sourceKey): array
    {
        $files = array_reverse($this->files($sourceKey));

        return array_map(
            fn (array $file): string => $this->resolvePath($sourceKey, $file['name']),
            $files
        );
    }

    public function clear(string $sourceKey): int
    {
        $source = $this->source($sourceKey);

        if ($source === null || !$source['writable']) {
            throw new RuntimeException('Этот источник доступен только для чтения');
        }

        $files = $this->files($sourceKey);
        $cleared = 0;

        foreach ($files as $index => $file) {
            $path = $this->resolvePath($sourceKey, $file['name']);

            $done = $index === 0
                ? @file_put_contents($path, '') !== false
                : @unlink($path);

            if ($done) {
                $cleared++;
            }
        }

        if ($files !== [] && $cleared === 0) {
            throw new RuntimeException('Не удалось очистить логи источника');
        }

        return $cleared;
    }

    private function rawEntries(string $path, int $maxScan, string $format): Generator
    {
        $pattern = $format === 'laravel' ? self::LARAVEL_ENTRY : self::PLAIN_ENTRY;

        $pending = [];

        foreach ($this->linesReversed($path, $maxScan) as $line) {
            $line = rtrim($line, "\r");

            if ($line === '' && $pending === []) {
                continue;
            }

            if (preg_match($pattern, $line) === 1) {
                array_unshift($pending, $line);
                yield implode("\n", $pending);
                $pending = [];

                continue;
            }

            array_unshift($pending, $line);

            if (count($pending) >= self::MAX_ENTRY_LINES) {
                yield implode("\n", $pending);
                $pending = [];
            }
        }

        if ($pending !== []) {
            yield implode("\n", $pending);
        }
    }

    private function linesReversed(string $path, int $maxScan): Generator
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            $size = filesize($path) ?: 0;
            $position = $size;
            $stopAt = max(0, $size - $maxScan);
            $chunkSize = (int) config('logs.chunk_size', 128 * 1024);

            $incomplete = '';

            while ($position > $stopAt) {
                $length = (int) min($chunkSize, $position - $stopAt);
                $position -= $length;

                fseek($handle, $position);
                $chunk = fread($handle, $length);

                if ($chunk === false) {
                    break;
                }

                $lines = explode("\n", $chunk . $incomplete);
                $incomplete = array_shift($lines);

                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    yield $lines[$i];
                }
            }

            if ($incomplete !== '') {
                yield $incomplete;
            }
        } finally {
            fclose($handle);
        }
    }

    private function parse(string $raw, string $format): array
    {
        $lines = explode("\n", $raw);
        $head = array_shift($lines) ?? '';

        $entry = [
            'time' => null,
            'level' => 'info',
            'channel' => null,
            'message' => $head,
            'context' => null,
            'trace' => null,
            'raw' => $raw,
        ];

        if ($format === 'laravel' && preg_match(self::LARAVEL_ENTRY, $head, $matches) === 1) {
            $entry['time'] = $matches[1];
            $entry['channel'] = $matches[2];
            $entry['level'] = $this->normalizeLevel($matches[3]);
            $entry['message'] = trim($matches[4]);
        } elseif (preg_match(self::PLAIN_ENTRY, $head, $matches) === 1) {
            $entry['time'] = str_replace(',', '.', $matches[1]);
            $entry['message'] = trim($matches[2]);
            $entry['level'] = $this->guessLevel($matches[2]);

            // «- pacs_core.py - INFO - текст»: так пишут lpr и analytic
            if (preg_match(self::PLAIN_MODULE, $entry['message'], $parts) === 1) {
                $level = $this->matchLevel($parts[2]);

                if ($level !== null) {
                    $entry['channel'] = trim($parts[1]);
                    $entry['level'] = $level;
                    $entry['message'] = trim($parts[3]);
                }
            }
        } else {
            $entry['level'] = $this->guessLevel($head);
        }

        [$message, $context] = $this->extractContext($entry['message']);
        $entry['message'] = $message;
        $entry['context'] = $context;

        if ($lines !== []) {
            $entry['trace'] = trim(implode("\n", $lines));
        }

        return $entry;
    }

    private function extractContext(string $message): array
    {
        $position = strcspn($message, '{[');

        if ($position >= strlen($message)) {
            return [$message, null];
        }

        $candidate = substr($message, $position);
        $decoded = json_decode($candidate, true);

        if (!is_array($decoded) || $decoded === []) {
            if (str_starts_with($candidate, '{"exception"')) {
                return [trim(substr($message, 0, $position)), $candidate];
            }

            return [$message, null];
        }

        return [trim(substr($message, 0, $position)), $candidate];
    }

    private function prettyContext(?string $context): ?string
    {
        if ($context === null) {
            return null;
        }

        $decoded = json_decode($context, true);

        if (!is_array($decoded) || $decoded === []) {
            return $context;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $context;
    }

    private function guessLevel(string $text): string
    {
        $head = substr($text, 0, 160);

        if (preg_match('/\b(EMERG(?:ENCY)?|ALERT|CRIT(?:ICAL)?|FATAL|SEVERE|ERROR|ERR|WARN(?:ING)?|NOTICE|INFO|DEBUG|TRACE|VERBOSE)\b/i', $head, $matches) === 1) {
            return $this->normalizeLevel($matches[1]);
        }

        return 'info';
    }

    private function normalizeLevel(string $level): string
    {
        return $this->matchLevel($level) ?? 'info';
    }

    private function matchLevel(string $level): ?string
    {
        $level = strtolower(trim($level));
        $level = self::LEVEL_ALIASES[$level] ?? $level;

        return array_key_exists($level, self::LEVELS) ? $level : null;
    }
}
