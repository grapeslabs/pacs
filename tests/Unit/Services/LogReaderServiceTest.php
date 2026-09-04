<?php

namespace Tests\Unit\Services;

use App\Services\LogReaderService;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class LogReaderServiceTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/logreader_test_'.uniqid();
        mkdir($this->dir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->dir);

        parent::tearDown();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path) || is_link($path)) {
            @unlink($path);

            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;

            if (is_dir($child) && !is_link($child)) {
                $this->removeDirectory($child);
            } else {
                @unlink($child);
            }
        }

        @rmdir($path);
    }

    private function configureSource(string $key = 'test', array $overrides = []): void
    {
        Config::set('logs.sources', [
            $key => array_merge([
                'label' => 'Тестовый источник',
                'path' => $this->dir,
                'format' => 'laravel',
                'writable' => true,
                'enabled' => true,
            ], $overrides),
        ]);
    }

    private function writeFile(string $name, string $content, ?int $mtime = null): string
    {
        $path = $this->dir.'/'.$name;
        file_put_contents($path, $content);

        if ($mtime !== null) {
            touch($path, $mtime);
        }

        return $path;
    }

    private function service(): LogReaderService
    {
        return $this->app->make(LogReaderService::class);
    }

    public function test_sources_skips_disabled_and_falls_back_label_to_key(): void
    {
        Config::set('logs.sources', [
            'on' => ['path' => $this->dir, 'enabled' => true],
            'off' => ['path' => $this->dir, 'enabled' => false, 'label' => 'Выключен'],
        ]);

        $sources = $this->service()->sources();

        $this->assertArrayNotHasKey('off', $sources);
        $this->assertSame('on', $sources['on']['label']);
        $this->assertTrue($sources['on']['exists']);
    }

    public function test_source_returns_null_for_unknown_key(): void
    {
        $this->configureSource();

        $this->assertNull($this->service()->source('nope'));
        $this->assertNotNull($this->service()->source('test'));
    }

    public function test_files_filters_by_extension_and_sorts_newest_first(): void
    {
        $this->configureSource();

        $this->writeFile('laravel.log', 'x', mtime: 1000);
        $this->writeFile('laravel.log.1', 'x', mtime: 500);
        $this->writeFile('plain.txt', 'x', mtime: 1500);
        $this->writeFile('ignored.php', 'x', mtime: 2000);
        $this->writeFile('noext', 'x', mtime: 2000);

        $names = array_column($this->service()->files('test'), 'name');

        $this->assertSame(['plain.txt', 'laravel.log', 'laravel.log.1'], $names);
    }

    public function test_files_returns_empty_for_missing_directory(): void
    {
        $this->configureSource(overrides: ['path' => $this->dir.'/does-not-exist']);

        $this->assertSame([], $this->service()->files('test'));
    }

    public function test_default_source_prefers_source_with_files(): void
    {
        Config::set('logs.sources', [
            'empty' => ['path' => $this->dir.'/empty-dir', 'enabled' => true],
            'has-files' => ['path' => $this->dir, 'enabled' => true],
        ]);
        $this->writeFile('a.log', 'x');

        $this->assertSame('has-files', $this->service()->defaultSource());
    }

    public function test_default_source_falls_back_to_first_when_none_have_files(): void
    {
        Config::set('logs.sources', [
            'first' => ['path' => $this->dir.'/empty-a', 'enabled' => true],
            'second' => ['path' => $this->dir.'/empty-b', 'enabled' => true],
        ]);

        $this->assertSame('first', $this->service()->defaultSource());
    }

    public function test_resolve_path_rejects_directory_traversal(): void
    {
        $this->configureSource();
        $this->writeFile('a.log', 'x');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Файл лога не найден');

        $this->service()->resolvePath('test', '../../../../etc/passwd');
    }

    public function test_resolve_path_rejects_a_file_inside_a_subdirectory(): void
    {
        $this->configureSource();
        mkdir($this->dir.'/sub');
        file_put_contents($this->dir.'/sub/inner.log', 'x');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Файл лога не найден');

        $this->service()->resolvePath('test', 'sub/inner.log');
    }

    public function test_resolve_path_rejects_a_symlink_pointing_outside_the_log_directory(): void
    {
        $this->configureSource();
        $outside = sys_get_temp_dir().'/logreader_outside_'.uniqid().'.log';
        file_put_contents($outside, 'секрет');
        symlink($outside, $this->dir.'/escape.log');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Файл лога не найден');

            $this->service()->resolvePath('test', 'escape.log');
        } finally {
            @unlink($outside);
        }
    }

    public function test_resolve_path_rejects_missing_file(): void
    {
        $this->configureSource();

        $this->expectException(RuntimeException::class);

        $this->service()->resolvePath('test', 'missing.log');
    }

    public function test_resolve_path_rejects_unknown_source(): void
    {
        $this->configureSource();

        $this->expectException(RuntimeException::class);

        $this->service()->resolvePath('nope', 'a.log');
    }

    public function test_resolve_path_returns_real_path_for_existing_file(): void
    {
        $this->configureSource();
        $path = $this->writeFile('a.log', 'x');

        $this->assertSame(realpath($path), $this->service()->resolvePath('test', 'a.log'));
    }

    public function test_entries_are_returned_newest_first_with_stack_trace_reattached(): void
    {
        $this->configureSource();
        $this->writeFile('laravel.log', implode("\n", [
            '[2026-01-01 10:00:00] local.INFO: First message',
            '[2026-01-01 10:00:01] local.ERROR: Second message',
            '#0 Stack trace line 1',
            '#1 Stack trace line 2',
            '[2026-01-01 10:00:02] local.WARNING: Third message',
        ]));

        $entries = $this->service()->entries('test')['entries'];

        $this->assertCount(3, $entries);
        $this->assertSame('Third message', $entries[0]['message']);
        $this->assertSame('warning', $entries[0]['level']);

        $this->assertSame('Second message', $entries[1]['message']);
        $this->assertSame('error', $entries[1]['level']);
        $this->assertSame("#0 Stack trace line 1\n#1 Stack trace line 2", $entries[1]['trace']);

        $this->assertSame('First message', $entries[2]['message']);
        $this->assertSame('info', $entries[2]['level']);
        $this->assertNull($entries[2]['trace']);
    }

    public function test_entries_normalizes_level_aliases(): void
    {
        $this->configureSource();
        $this->writeFile('laravel.log', implode("\n", [
            '[2026-01-01 10:00:00] local.WARN: aliased warning',
            '[2026-01-01 10:00:01] local.CRIT: aliased critical',
            '[2026-01-01 10:00:02] local.FATAL: aliased fatal',
        ]));

        $levels = array_column($this->service()->entries('test')['entries'], 'level');

        $this->assertSame(['critical', 'critical', 'warning'], $levels);
    }

    public function test_entries_extracts_json_context_from_message_tail(): void
    {
        $this->configureSource();
        $this->writeFile('laravel.log',
            '[2026-01-01 10:00:00] local.ERROR: Something failed {"user_id":42,"reason":"timeout"}'
        );

        $entry = $this->service()->entries('test')['entries'][0];

        $this->assertSame('Something failed', $entry['message']);
        $this->assertNotNull($entry['context']);
        $this->assertStringContainsString('"user_id": 42', $entry['context']);
    }

    public function test_entries_does_not_treat_invalid_json_looking_text_as_context(): void
    {
        $this->configureSource();
        $this->writeFile('laravel.log',
            '[2026-01-01 10:00:00] local.INFO: Value is {not valid json} here'
        );

        $entry = $this->service()->entries('test')['entries'][0];

        $this->assertSame('Value is {not valid json} here', $entry['message']);
        $this->assertNull($entry['context']);
    }

    public function test_entries_parses_plain_module_format(): void
    {
        $this->configureSource(overrides: ['format' => 'plain']);
        $this->writeFile('server.log',
            '2026-01-01 10:00:00 - pacs_core.py - ERROR - connection refused'
        );

        $entry = $this->service()->entries('test')['entries'][0];

        $this->assertSame('error', $entry['level']);
        $this->assertSame('pacs_core.py', $entry['channel']);
        $this->assertSame('connection refused', $entry['message']);
    }

    public function test_entries_guesses_level_for_unstructured_plain_lines(): void
    {
        $this->configureSource(overrides: ['format' => 'plain']);
        $this->writeFile('server.log', implode("\n", [
            '2026-01-01 10:00:00 something went wrong: ERROR occurred',
            '2026-01-01 10:00:01 all good here',
        ]));

        $levels = array_column($this->service()->entries('test')['entries'], 'level');

        $this->assertSame(['info', 'error'], $levels);
    }

    public function test_entries_filters_by_level(): void
    {
        $this->configureSource();
        $this->writeFile('laravel.log', implode("\n", [
            '[2026-01-01 10:00:00] local.INFO: info message',
            '[2026-01-01 10:00:01] local.ERROR: error message',
        ]));

        $result = $this->service()->entries('test', ['levels' => ['error']]);

        $this->assertCount(1, $result['entries']);
        $this->assertSame('error message', $result['entries'][0]['message']);
    }

    public function test_entries_filters_by_search_case_insensitively(): void
    {
        $this->configureSource();
        $this->writeFile('laravel.log', implode("\n", [
            '[2026-01-01 10:00:00] local.INFO: connection to database established',
            '[2026-01-01 10:00:01] local.INFO: user logged in',
        ]));

        $result = $this->service()->entries('test', ['search' => 'DATABASE']);

        $this->assertCount(1, $result['entries']);
        $this->assertSame('connection to database established', $result['entries'][0]['message']);
    }

    public function test_entries_paginates_respecting_per_page(): void
    {
        Config::set('logs.per_page', 2);
        $this->configureSource();

        $lines = [];
        for ($i = 0; $i < 5; $i++) {
            $lines[] = sprintf('[2026-01-01 10:00:%02d] local.INFO: message %d', $i, $i);
        }
        $this->writeFile('laravel.log', implode("\n", $lines));

        $page1 = $this->service()->entries('test', [], 1);
        $page2 = $this->service()->entries('test', [], 2);
        $page3 = $this->service()->entries('test', [], 3);

        $this->assertSame(['message 4', 'message 3'], array_column($page1['entries'], 'message'));
        $this->assertTrue($page1['has_more']);

        $this->assertSame(['message 2', 'message 1'], array_column($page2['entries'], 'message'));

        $this->assertSame(['message 0'], array_column($page3['entries'], 'message'));
        $this->assertFalse($page3['has_more']);
    }

    public function test_entries_reports_aggregate_file_metadata(): void
    {
        $this->configureSource();
        $this->writeFile('a.log', str_repeat('x', 10), mtime: 100);
        $this->writeFile('b.log', str_repeat('x', 20), mtime: 200);

        $result = $this->service()->entries('test');

        $this->assertSame(2, $result['files']);
        $this->assertSame(30, $result['size']);
        $this->assertSame(200, $result['newest']);
        $this->assertSame(100, $result['oldest']);
    }

    public function test_entries_marks_truncated_when_scan_budget_is_exceeded(): void
    {
        Config::set('logs.max_scan_bytes', 10);
        $this->configureSource();
        $this->writeFile('laravel.log', str_repeat('a', 1000));

        $result = $this->service()->entries('test');

        $this->assertTrue($result['truncated']);
    }

    public function test_entries_throws_for_unknown_source(): void
    {
        $this->configureSource();

        $this->expectException(RuntimeException::class);

        $this->service()->entries('nope');
    }

    public function test_paths_returns_oldest_to_newest(): void
    {
        $this->configureSource();
        $older = $this->writeFile('a.log', 'x', mtime: 100);
        $newer = $this->writeFile('b.log', 'x', mtime: 200);

        $this->assertSame(
            [realpath($older), realpath($newer)],
            $this->service()->paths('test'),
        );
    }

    public function test_clear_truncates_newest_file_and_deletes_the_rest(): void
    {
        $this->configureSource();
        $older = $this->writeFile('a.log', 'old content', mtime: 100);
        $newer = $this->writeFile('b.log', 'new content', mtime: 200);

        $cleared = $this->service()->clear('test');

        $this->assertSame(2, $cleared);
        $this->assertFileDoesNotExist($older);
        $this->assertFileExists($newer);
        $this->assertSame('', file_get_contents($newer));
    }

    public function test_clear_rejects_readonly_source(): void
    {
        $this->configureSource(overrides: ['writable' => false]);
        $this->writeFile('a.log', 'x');

        $this->expectException(RuntimeException::class);

        $this->service()->clear('test');
    }

    public function test_clear_rejects_unknown_source(): void
    {
        $this->configureSource();

        $this->expectException(RuntimeException::class);

        $this->service()->clear('nope');
    }
}
