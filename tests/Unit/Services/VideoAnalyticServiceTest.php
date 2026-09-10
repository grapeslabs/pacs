<?php

namespace Tests\Unit\Services;

use App\Services\VideoAnalyticService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class VideoAnalyticServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.va.url', 'http://va.test');
        Config::set('services.va.timeout', 10);
        Config::set('services.va.timedelay', 200);
        Config::set('services.ms.rtsp_url', 'rtsp://media.test:8554');
        Config::set('app.debug', false);
    }

    private function service(): VideoAnalyticService
    {
        return $this->app->make(VideoAnalyticService::class);
    }

    public function test_camera_list_sends_get_with_default_user_id(): void
    {
        Http::fake(['va.test/*' => Http::response(['cameras' => []], 200)]);

        $result = $this->service()->cameraList();

        $this->assertSame(['cameras' => []], $result);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'http://va.test/api/v1/camera/list?')
                && $request['user_id'] === '1';
        });
    }

    public function test_request_returns_empty_array_on_server_error(): void
    {
        Http::fake(['va.test/*' => Http::response('boom', 500)]);

        $this->assertSame([], $this->service()->cameraList());
    }

    public function test_request_returns_empty_array_on_connection_failure(): void
    {
        Http::fake(['va.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('refused')]);

        $this->assertSame([], $this->service()->cameraList());
    }

    public function test_request_treats_404_as_ignored_not_found_when_debug_is_off(): void
    {
        Config::set('app.debug', false);
        Http::fake(['va.test/*' => Http::response('', 404)]);

        $this->assertSame(
            ['ok' => true, 'note' => 'not_found_ignored'],
            $this->service()->cameraList(),
        );
    }

    public function test_request_fails_closed_on_404_when_debug_is_on(): void
    {
        Config::set('app.debug', true);
        Http::fake(['va.test/*' => Http::response('', 404)]);

        $this->assertSame([], $this->service()->cameraList());
    }

    public function test_person_list_and_delete_hit_expected_endpoints(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->personList();
        $this->service()->personDelete('person-42');

        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), '/api/v1/person/getinfo'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
            && str_contains($r->url(), '/api/v1/person/del')
            && str_contains($r->url(), 'person_id=person-42'));
    }

    public function test_person_info_gets_person_id(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->personInfo('person-7');

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_contains($r->url(), '/api/v1/person/getinfo')
            && str_contains($r->url(), 'person_id=person-7'));
    }

    public function test_camera_create_builds_stream_and_default_zones(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraCreate('cam-1', 'Camera 1', 'desc', []);

        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertStringContainsString('/api/v1/camera/create', $r->url());
            $this->assertSame('cam-1', $r['stream_info']['id']);
            $this->assertSame('rtsp://media.test:8554/rtsp/cam-1', $r['stream_info']['url']);
            $this->assertSame('Camera 1', $r['stream_info']['name']);
            $this->assertSame(0, $r['detection_face']['is_detection']);
            $this->assertSame(75, $r['detection_face']['threshold']);
            $this->assertSame([], $r['detection_face']['zone']);
            $this->assertSame([], $r['detection_figure']['zones']);

            return true;
        });
    }

    public function test_camera_create_converts_face_zone_to_pixels(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'is_face_detection' => true,
            'is_face_recognition' => true,
            'face_recognition_sensitivity' => 90,
            'has_face_detection_zone' => true,
            'face_detection_zone' => ['x1' => 0.1, 'y1' => 0.2, 'x2' => 0.9, 'y2' => 0.8],
            'video_width' => 1000,
            'video_height' => 500,
        ]);

        Http::assertSent(function (Request $r) {
            $this->assertSame(1, $r['detection_face']['is_detection']);
            $this->assertSame(1, $r['detection_face']['is_recognize']);
            $this->assertSame(90, $r['detection_face']['threshold']);
            $this->assertSame(
                ['x1' => 100, 'y1' => 100, 'x2' => 900, 'y2' => 400],
                $r['detection_face']['zone'],
            );

            return true;
        });
    }

    public function test_camera_create_accepts_face_zone_as_json_string(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_face_detection_zone' => true,
            'face_detection_zone' => json_encode(['x1' => 0.5, 'y1' => 0.5, 'x2' => 1.0, 'y2' => 1.0]),
            'video_width' => 200,
            'video_height' => 100,
        ]);

        Http::assertSent(function (Request $r) {
            $this->assertSame(
                ['x1' => 100, 'y1' => 50, 'x2' => 200, 'y2' => 100],
                $r['detection_face']['zone'],
            );

            return true;
        });
    }

    public function test_camera_create_ignores_unparsable_face_zone(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_face_detection_zone' => true,
            'face_detection_zone' => 'not json',
        ]);

        Http::assertSent(fn (Request $r) => $r['detection_face']['zone'] === []);
    }

    public function test_camera_create_converts_rectangle_motion_zones_to_pixels(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'is_motion_detection' => true,
            'motion_detection_direction' => 'B',
            'has_motion_detection_zone' => true,
            'motion_detection_zones' => [
                ['type' => 'rectangles', 'zones' => [['x1' => 0.0, 'y1' => 0.0, 'x2' => 0.5, 'y2' => 0.5]]],
            ],
            'video_width' => 1000,
            'video_height' => 1000,
        ]);

        Http::assertSent(function (Request $r) {
            $this->assertSame(1, $r['detection_figure']['is_active']);
            $this->assertSame('B', $r['detection_figure']['direction']);
            $this->assertSame(
                ['rectangles' => [['x1' => 0, 'y1' => 0, 'x2' => 500, 'y2' => 500]]],
                $r['detection_figure']['zones'],
            );

            return true;
        });
    }

    public function test_camera_create_converts_polygon_motion_zones_remapping_point_keys(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_motion_detection_zone' => true,
            'motion_detection_zones' => [
                [
                    'type' => 'polygons',
                    'zones' => [
                        [
                            ['x1' => 0.1, 'y1' => 0.1],
                            ['x1' => 0.5, 'y1' => 0.9],
                        ],
                    ],
                ],
            ],
            'video_width' => 100,
            'video_height' => 100,
        ]);

        Http::assertSent(function (Request $r) {
            $this->assertSame(
                ['polygons' => [[['x' => 10, 'y' => 10], ['x' => 50, 'y' => 90]]]],
                $r['detection_figure']['zones'],
            );

            return true;
        });
    }

    public function test_camera_delete_posts_cam_id(): void
    {
        Http::fake(['va.test/*' => Http::response(['ok' => true], 200)]);

        $this->service()->cameraDelete('cam-99');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && str_contains($r->url(), '/api/v1/camera/suspend')
            && $r['cam_id'] === 'cam-99');
    }

    public function test_person_create_returns_empty_without_photos_and_sends_nothing(): void
    {
        Http::fake();

        $result = $this->service()->personCreate('John', []);

        $this->assertSame([], $result);
        Http::assertNothingSent();
    }

    public function test_person_create_uploads_photos_as_multipart(): void
    {
        Http::fake(['va.test/*' => Http::response(['person_id' => 'abc'], 200)]);

        $path = tempnam(sys_get_temp_dir(), 'photo');
        file_put_contents($path, 'fake-image-bytes');

        try {
            $result = $this->service()->personCreate('John Doe', [$path], personUid: 'p-1');

            $this->assertSame(['person_id' => 'abc'], $result);
            Http::assertSent(function (Request $r) use ($path) {
                $field = fn (string $name) => collect($r->data())->firstWhere('name', $name)['contents'] ?? null;

                return $r->method() === 'POST'
                    && str_contains($r->url(), '/api/v1/person/add')
                    && $r->isMultipart()
                    && $r->hasFile('photos', filename: basename($path))
                    && $field('user_id') === '1'
                    && $field('desc') === 'John Doe'
                    && $field('person_id') === 'p-1';
            });
        } finally {
            @unlink($path);
        }
    }

    public function test_person_create_returns_empty_array_on_failure(): void
    {
        Http::fake(['va.test/*' => Http::response('error', 500)]);

        $path = tempnam(sys_get_temp_dir(), 'photo');
        file_put_contents($path, 'x');

        try {
            $this->assertSame([], $this->service()->personCreate('John', [$path]));
        } finally {
            @unlink($path);
        }
    }
}
