<?php

namespace Tests\Unit\Services;

use App\Services\MediaServerService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaServerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ms.url', 'http://ms.test');
        Config::set('services.ms.timeout', 5);
        Config::set('app.url', 'https://pacs.test');
    }

    private function service(): MediaServerService
    {
        return new MediaServerService();
    }

    private function fakeOk(array $body = ['status' => 'success']): void
    {
        Http::fake(['ms.test/*' => Http::response($body, 200)]);
    }

    public function test_create_stream_sends_full_payload_and_returns_uid(): void
    {
        $this->fakeOk(['status' => 'success', 'data' => ['stream_uid' => 'cam-1']]);

        $uid = $this->service()->createStream('rtsp://cam.test/1', 'cam-1', 48);

        $this->assertSame('cam-1', $uid);
        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertSame('http://ms.test/api/v1/stream/add', $r->url());
            $this->assertSame('cam-1', $r['stream_uid']);
            $this->assertSame('rtsp://cam.test/1', $r['url']);
            $this->assertSame('/data/streams/cam-1', $r['output_dir']);
            $this->assertSame('/rtsp/cam-1', $r['mount_path']);
            $this->assertSame(10, $r['segment_duration']);
            $this->assertSame(48, $r['depthhours']);
            $this->assertTrue($r['enable_hls']);
            $this->assertTrue($r['enable_rtsp']);

            return true;
        });
    }

    public function test_create_stream_returns_the_uid_reported_by_the_server_not_the_requested_one(): void
    {
        $this->fakeOk(['status' => 'success', 'data' => ['stream_uid' => 'server-assigned']]);

        $this->assertSame('server-assigned', $this->service()->createStream('rtsp://cam.test/1', 'requested', 1));
    }

    public function test_create_stream_throws_a_bare_message_when_uid_is_missing(): void
    {
        $this->fakeOk(['status' => 'success', 'data' => []]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Media Server не вернул stream_uid при создании.');

        $this->service()->createStream('rtsp://cam.test/1', 'cam-1', 1);
    }

    public function test_404_without_ignore_flag_becomes_a_wrapped_exception(): void
    {
        Http::fake(['ms.test/*' => Http::response('', 404)]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Сбой Media Server: Эндпоинт или поток не найден (HTTP 404).');

        $this->service()->pauseStream('cam-1');
    }

    public function test_error_status_in_body_is_reported_even_on_http_200(): void
    {
        Http::fake(['ms.test/*' => Http::response(['status' => 'error', 'error' => 'поток занят'], 200)]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Сбой Media Server: поток занят');

        $this->service()->resumeStream('cam-1');
    }

    public function test_error_status_without_message_falls_back_to_a_generic_text(): void
    {
        Http::fake(['ms.test/*' => Http::response(['status' => 'error'], 200)]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Сбой Media Server: Неизвестная ошибка Media Server');

        $this->service()->resumeStream('cam-1');
    }

    public function test_server_error_is_wrapped_too(): void
    {
        Http::fake(['ms.test/*' => Http::response('boom', 500)]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^Сбой Media Server: /');

        $this->service()->pauseStream('cam-1');
    }

    public function test_connection_failure_is_wrapped_too(): void
    {
        Http::fake(['ms.test/*' => fn () => throw new ConnectionException('refused')]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Сбой Media Server: refused');

        $this->service()->pauseStream('cam-1');
    }

    public function test_update_stream_payload(): void
    {
        $this->fakeOk();

        $this->service()->updateStream('cam-2', 'rtsp://cam.test/2', 12);

        Http::assertSent(function (Request $r) {
            $this->assertSame('http://ms.test/api/v1/stream/update', $r->url());
            $this->assertSame('cam-2', $r['stream_uid']);
            $this->assertSame('rtsp://cam.test/2', $r['url']);
            $this->assertSame(12, $r['depthhours']);
            $this->assertSame('/rtsp/cam-2', $r['mount_path']);

            return true;
        });
    }

    public function test_delete_stream_uses_delete_verb_and_tolerates_404(): void
    {
        Http::fake(['ms.test/*' => Http::response('', 404)]);

        $this->assertSame(['status' => 'success'], $this->service()->deleteStream('cam-3'));

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
            && $r->url() === 'http://ms.test/api/v1/stream/del');
    }

    public function test_pause_and_resume_hit_their_endpoints(): void
    {
        $this->fakeOk();

        $this->service()->pauseStream('cam-4');
        $this->service()->resumeStream('cam-4');

        Http::assertSent(fn (Request $r) => $r->url() === 'http://ms.test/api/v1/stream/pause' && $r['stream_uid'] === 'cam-4');
        Http::assertSent(fn (Request $r) => $r->url() === 'http://ms.test/api/v1/stream/resume' && $r['stream_uid'] === 'cam-4');
    }

    public function test_download_archive_puts_the_time_range_into_the_query_string(): void
    {
        $this->fakeOk(['status' => 'success', 'request_id' => 'r-1']);

        $this->service()->downloadArchive('cam-5', '2026-01-01T00:00:00', '2026-01-01T01:00:00');

        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertStringStartsWith('http://ms.test/api/v1/stream/cam-5/archive/download?', $r->url());
            $this->assertStringContainsString('startTime='.rawurlencode('2026-01-01T00:00:00'), $r->url());
            $this->assertStringContainsString('endTime='.rawurlencode('2026-01-01T01:00:00'), $r->url());

            return true;
        });
    }

    public function test_download_status_uses_get(): void
    {
        $this->fakeOk(['status' => 'ready']);

        $this->service()->downloadStatus('cam-6', 'req-9');

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === 'http://ms.test/api/v1/stream/cam-6/archive/status/req-9');
    }

    public function test_download_archive_file_only_builds_a_public_url(): void
    {
        Http::fake();

        $result = $this->service()->downloadArchiveFile('cam-7', 'req-3');

        $this->assertSame(
            ['url' => 'https://pacs.test/media/api/v1/stream/cam-7/archive/download/req-3.mp4'],
            $result,
        );
        Http::assertNothingSent();
    }
}
