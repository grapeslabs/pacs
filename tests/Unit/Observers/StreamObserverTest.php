<?php

namespace Tests\Unit\Observers;

use App\Models\Storage;
use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticLprService;
use App\Services\VideoAnalyticService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StreamObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.va.enabled', false);
        Config::set('services.lpr.enabled', false);
    }

    private function baseAttributes(array $overrides = []): array
    {
        $storage = Storage::create(['name' => 'Test storage', 'type' => 'ftp', 'data' => []]);

        return array_merge([
            'storage_id' => $storage->id,
            'name' => 'Camera 1',
            'location' => 'Entrance',
            'rtsp' => 'rtsp://cam.test/1',
            'archive_time' => 24,
            'is_active' => true,
        ], $overrides);
    }

    private function mockMediaServerCreate(string $returnedUid = 'ms-generated-uid'): void
    {
        $this->mock(MediaServerService::class, function ($mock) use ($returnedUid) {
            $mock->shouldReceive('createStream')->once()->andReturn($returnedUid);
        });
    }

    public function test_creating_persists_the_uid_returned_by_media_server(): void
    {
        $this->mockMediaServerCreate('ms-generated-uid');

        $stream = Stream::create($this->baseAttributes());

        $this->assertSame('ms-generated-uid', $stream->fresh()->uid);
    }

    public function test_creating_an_inactive_stream_pauses_it_immediately(): void
    {
        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once()->andReturn('uid-1');
            $mock->shouldReceive('pauseStream')->once()->with('uid-1')->andReturn([]);
        });

        Stream::create($this->baseAttributes(['is_active' => false]));
    }

    public function test_creating_an_active_stream_never_pauses_it(): void
    {
        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once()->andReturn('uid-1');
            $mock->shouldNotReceive('pauseStream');
        });

        Stream::create($this->baseAttributes(['is_active' => true]));
    }

    public function test_creating_registers_camera_in_video_analytics_when_enabled(): void
    {
        Config::set('services.va.enabled', true);
        $this->mockMediaServerCreate('uid-1');
        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('cameraCreate')->once()
                ->with('uid-1', 'Camera 1', 'Entrance', ['is_face_detection' => 1])
                ->andReturn(['ok' => true]);
        });

        Stream::create($this->baseAttributes(['va_options' => ['is_face_detection' => 1]]));
    }

    public function test_creating_skips_video_analytics_and_lpr_when_both_disabled(): void
    {
        $this->mockMediaServerCreate('uid-1');
        $this->mock(VideoAnalyticService::class);
        $this->mock(VideoAnalyticLprService::class);

        Stream::create($this->baseAttributes());

        $this->addToAssertionCount(1);
    }

    public function test_creating_rolls_back_media_server_stream_and_rethrows_on_failure(): void
    {
        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once()->andThrow(new Exception('media server down'));
            $mock->shouldReceive('deleteStream')->once()->andReturn([]);
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('media server down');

        Stream::create($this->baseAttributes());
    }

    public function test_updating_rtsp_pushes_update_to_media_server(): void
    {
        $stream = $this->createPersistedStream();

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('updateStream')->once()->with($stream->uid, 'rtsp://new.test', 48)->andReturn([]);
        });

        $stream->update(['rtsp' => 'rtsp://new.test', 'archive_time' => 48]);
    }

    public function test_updating_is_active_toggles_pause_and_resume(): void
    {
        $stream = $this->createPersistedStream(['is_active' => true]);

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('pauseStream')->once()->with($stream->uid)->andReturn([]);
        });
        $stream->update(['is_active' => false]);

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('resumeStream')->once()->with($stream->uid)->andReturn([]);
        });
        $stream->update(['is_active' => true]);
    }

    public function test_updating_unrelated_field_touches_nothing(): void
    {
        $stream = $this->createPersistedStream();

        $this->mock(MediaServerService::class);

        $stream->update(['name' => 'Renamed camera']);

        $this->addToAssertionCount(1);
    }

    public function test_deleting_removes_stream_from_media_server_and_analytics(): void
    {
        $stream = $this->createPersistedStream();
        Config::set('services.va.enabled', true);
        Config::set('services.lpr.enabled', true);

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('deleteStream')->once()->with($stream->uid)->andReturn([]);
        });
        $this->mock(VideoAnalyticService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('cameraDelete')->once()->with($stream->uid)->andReturn(['ok' => true]);
        });
        $this->mock(VideoAnalyticLprService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('cameraDelete')->once()->with($stream->uid)->andReturn(['ok' => true]);
        });

        $stream->delete();
    }

    public function test_disabling_global_enable_forces_off_detection_flags(): void
    {
        $stream = $this->createPersistedStream(['va_options' => [
            'global_enable' => 1,
            'is_face_detection' => 1,
            'is_face_recognition' => 1,
            'is_motion_detection' => 1,
        ]]);
        Config::set('services.va.enabled', true);

        $this->mock(MediaServerService::class);
        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('cameraCreate')->once()
                ->withArgs(function ($uid, $name, $location, $options) {
                    return $options['is_face_detection'] === 0
                        && $options['is_face_recognition'] === 0
                        && $options['is_motion_detection'] === 0;
                })
                ->andReturn(['ok' => true]);
        });

        $stream->update(['va_options' => ['global_enable' => 0]]);
    }

    public function test_disabling_plate_recognition_forces_off_autocollect(): void
    {
        $stream = $this->createPersistedStream(['va_options' => [
            'global_enable' => 1,
            'is_plate_recognition' => 1,
            'is_plate_autocollect' => 1,
        ]]);
        Config::set('services.lpr.enabled', true);

        $this->mock(MediaServerService::class);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('cameraCreate')->once()
                ->withArgs(fn ($uid, $name, $location, $options) => $options['is_plate_autocollect'] === 0)
                ->andReturn(['ok' => true]);
        });

        $stream->update(['va_options' => ['global_enable' => 1, 'is_plate_recognition' => 0, 'is_plate_autocollect' => 1]]);
    }

    private function createPersistedStream(array $overrides = []): Stream
    {
        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once()->andReturn('uid-'.uniqid());
        });

        return Stream::create($this->baseAttributes($overrides));
    }
}
