<?php

namespace Tests\Unit\Services;

use App\Services\VideoAnalyticLprService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoAnalyticLprServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.lpr.url', 'http://lpr.test');
        Config::set('services.lpr.timeout', 10);
        Config::set('services.lpr.timedelay', 200);
        Config::set('services.ms.rtsp_url', 'rtsp://media.test:8554');
        Config::set('app.debug', false);
    }

    private function service(): VideoAnalyticLprService
    {
        return $this->app->make(VideoAnalyticLprService::class);
    }

    private function fakeOk(): void
    {
        Http::fake(['lpr.test/*' => Http::response(['ok' => true], 200)]);
    }

    public function test_camera_list_is_a_get_with_default_user_id(): void
    {
        $this->fakeOk();

        $this->assertSame(['ok' => true], $this->service()->cameraList());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'http://lpr.test/api/v1/camera/list?')
            && $r['user_id'] === '1');
    }

    public function test_camera_create_builds_stream_info_and_default_recognition_block(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', 'Ворота', 'въезд', []);

        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertStringContainsString('/api/v1/camera/create', $r->url());
            $this->assertSame('cam-1', $r['stream_info']['id']);
            $this->assertSame('rtsp://media.test:8554/rtsp/cam-1', $r['stream_info']['url']);
            $this->assertSame('Ворота', $r['stream_info']['name']);
            $this->assertSame('въезд', $r['stream_info']['description']);
            $this->assertSame(200, $r['stream_info']['timedelay']);
            $this->assertSame(1, $r['stream_info']['resize']);
            $this->assertSame(0, $r['recognition_plate']['is_recognition']);
            $this->assertSame(75, $r['recognition_plate']['threshold']);
            $this->assertSame([], $r['recognition_plate']['zone']);

            return true;
        });
    }

    public function test_camera_create_normalizes_null_description_to_empty_string(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', 'Ворота', null);

        Http::assertSent(fn (Request $r) => $r['stream_info']['description'] === '');
    }

    public function test_camera_create_casts_recognition_flag_and_keeps_custom_threshold(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'is_plate_recognition' => true,
            'plate_recognition_sensitivity' => 42,
        ]);

        Http::assertSent(function (Request $r) {
            $this->assertSame(1, $r['recognition_plate']['is_recognition']);
            $this->assertSame(42, $r['recognition_plate']['threshold']);

            return true;
        });
    }

    public function test_zone_is_converted_from_relative_to_absolute_pixels(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_plate_recognition_zone' => true,
            'plate_recognition_zone' => ['x1' => 0.1, 'y1' => 0.2, 'x2' => 0.9, 'y2' => 0.8],
            'video_width' => 1000,
            'video_height' => 500,
        ]);

        Http::assertSent(fn (Request $r) => $r['recognition_plate']['zone']
            === ['x1' => 100, 'y1' => 100, 'x2' => 900, 'y2' => 400]);
    }

    public function test_zone_falls_back_to_full_hd_when_video_size_is_unknown(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_plate_recognition_zone' => true,
            'plate_recognition_zone' => ['x1' => 0, 'y1' => 0, 'x2' => 1, 'y2' => 1],
        ]);

        Http::assertSent(fn (Request $r) => $r['recognition_plate']['zone']
            === ['x1' => 0, 'y1' => 0, 'x2' => 1920, 'y2' => 1080]);
    }

    public function test_zone_accepts_a_json_string(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_plate_recognition_zone' => true,
            'plate_recognition_zone' => json_encode(['x1' => 0.5, 'y1' => 0.5, 'x2' => 1.0, 'y2' => 1.0]),
            'video_width' => 200,
            'video_height' => 100,
        ]);

        Http::assertSent(fn (Request $r) => $r['recognition_plate']['zone']
            === ['x1' => 100, 'y1' => 50, 'x2' => 200, 'y2' => 100]);
    }

    public function test_zone_is_dropped_when_the_flag_is_off_even_if_coordinates_are_present(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_plate_recognition_zone' => false,
            'plate_recognition_zone' => ['x1' => 0.1, 'y1' => 0.1, 'x2' => 0.2, 'y2' => 0.2],
        ]);

        Http::assertSent(fn (Request $r) => $r['recognition_plate']['zone'] === []);
    }

    public function test_unparsable_zone_is_ignored(): void
    {
        $this->fakeOk();

        $this->service()->cameraCreate('cam-1', vaOptions: [
            'has_plate_recognition_zone' => true,
            'plate_recognition_zone' => 'не json',
        ]);

        Http::assertSent(fn (Request $r) => $r['recognition_plate']['zone'] === []);
    }

    public function test_camera_delete_posts_to_suspend(): void
    {
        $this->fakeOk();

        $this->service()->cameraDelete('cam-9');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && str_contains($r->url(), '/api/v1/camera/suspend')
            && $r['cam_id'] === 'cam-9');
    }

    public function test_car_create_posts_plate_and_description(): void
    {
        $this->fakeOk();

        $this->service()->carCreate('A123BC777', 'директор');

        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertStringContainsString('/api/v1/car/add', $r->url());
            $this->assertSame('A123BC777', $r['plate']);
            $this->assertSame('директор', $r['desc']);

            return true;
        });
    }

    public function test_car_delete_sends_the_plate_in_the_query_string(): void
    {
        $this->fakeOk();

        $this->service()->carDelete('A123BC777');

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
            && str_contains($r->url(), '/api/v1/car/del')
            && str_contains($r->url(), 'plate=A123BC777'));
    }

    public function test_car_info_and_car_list_are_gets(): void
    {
        $this->fakeOk();

        $this->service()->carList();
        $this->service()->carInfo('A123BC777');

        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), '/api/v1/car/list'));
        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_contains($r->url(), '/api/v1/car/info')
            && str_contains($r->url(), 'plate=A123BC777'));
    }

    public function test_failures_degrade_to_an_empty_array(): void
    {
        Http::fake(['lpr.test/*' => Http::response('boom', 500)]);

        $this->assertSame([], $this->service()->carCreate('A123BC777'));
    }

    public function test_404_is_treated_as_an_ignorable_not_found(): void
    {
        Http::fake(['lpr.test/*' => Http::response('', 404)]);

        $this->assertSame(
            ['ok' => true, 'note' => 'not_found_ignored'],
            $this->service()->carDelete('A123BC777'),
        );
    }
}
