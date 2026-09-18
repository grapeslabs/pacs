<?php

namespace Tests\Unit\Observers;

use App\Jobs\ProvisionStream;
use App\Jobs\UpdateStreamProvisioning;
use App\Models\Storage;
use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticLprService;
use App\Services\VideoAnalyticService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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

    private function provision(Stream $stream): void
    {
        (new ProvisionStream($stream->id))->handle(
            app(MediaServerService::class),
            app(VideoAnalyticService::class),
            app(VideoAnalyticLprService::class),
        );
    }

    private function runLastUpdateProvisioning(): void
    {
        Queue::pushed(UpdateStreamProvisioning::class)->last()->handle(
            app(MediaServerService::class),
            app(VideoAnalyticService::class),
            app(VideoAnalyticLprService::class),
        );
    }

    public function test_creating_generates_local_uid_without_calling_media_server(): void
    {
        Queue::fake();

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldNotReceive('createStream');
        });

        $stream = Stream::create($this->baseAttributes());

        $this->assertTrue(Str::isUuid($stream->fresh()->uid));
        Queue::assertPushed(ProvisionStream::class);
    }

    public function test_provision_job_pauses_an_inactive_stream(): void
    {
        Queue::fake();
        $stream = Stream::create($this->baseAttributes(['is_active' => false]));

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('createStream')->once()->with('rtsp://cam.test/1', $stream->uid, 24);
            $mock->shouldReceive('pauseStream')->once()->with($stream->uid)->andReturn([]);
        });

        $this->provision($stream);
    }

    public function test_provision_job_never_pauses_an_active_stream(): void
    {
        Queue::fake();
        $stream = Stream::create($this->baseAttributes(['is_active' => true]));

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once();
            $mock->shouldNotReceive('pauseStream');
        });

        $this->provision($stream);
    }

    public function test_provision_job_registers_camera_in_video_analytics_when_enabled(): void
    {
        Queue::fake();
        $stream = Stream::create($this->baseAttributes(['va_options' => ['is_face_detection' => 1]]));

        Config::set('services.va.enabled', true);

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once();
        });
        $this->mock(VideoAnalyticService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('cameraCreate')->once()
                ->with($stream->uid, 'Camera 1', 'Entrance', ['is_face_detection' => 1])
                ->andReturn(['ok' => true]);
        });

        $this->provision($stream);
    }

    public function test_provision_job_skips_video_analytics_and_lpr_when_both_disabled(): void
    {
        Queue::fake();
        $stream = Stream::create($this->baseAttributes());

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once();
        });
        $this->mock(VideoAnalyticService::class);
        $this->mock(VideoAnalyticLprService::class);

        $this->provision($stream);

        $this->addToAssertionCount(1);
    }

    public function test_provision_job_lets_the_failure_through_so_the_queue_retries(): void
    {
        Queue::fake();
        $stream = Stream::create($this->baseAttributes());

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('createStream')->once()->andThrow(new Exception('media server down'));
            $mock->shouldNotReceive('deleteStream');
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('media server down');

        $this->provision($stream);
    }

    public function test_updating_rtsp_pushes_update_to_media_server(): void
    {
        $stream = $this->createPersistedStream();

        $stream->update(['rtsp' => 'rtsp://new.test', 'archive_time' => 48]);
        Queue::assertPushed(UpdateStreamProvisioning::class);

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('updateStream')->once()->with($stream->uid, 'rtsp://new.test', 48)->andReturn([]);
        });

        $this->runLastUpdateProvisioning();
    }

    public function test_updating_is_active_toggles_pause_and_resume(): void
    {
        $stream = $this->createPersistedStream(['is_active' => true]);

        $stream->update(['is_active' => false]);
        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('pauseStream')->once()->with($stream->uid)->andReturn([]);
        });
        $this->runLastUpdateProvisioning();

        $stream->update(['is_active' => true]);
        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('resumeStream')->once()->with($stream->uid)->andReturn([]);
        });
        $this->runLastUpdateProvisioning();
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

        $stream->update(['va_options' => ['global_enable' => 0]]);
        Queue::assertPushed(UpdateStreamProvisioning::class);

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

        $this->runLastUpdateProvisioning();
    }

    public function test_disabling_plate_recognition_forces_off_autocollect(): void
    {
        $stream = $this->createPersistedStream(['va_options' => [
            'global_enable' => 1,
            'is_plate_recognition' => 1,
            'is_plate_autocollect' => 1,
        ]]);
        Config::set('services.lpr.enabled', true);

        $stream->update(['va_options' => ['global_enable' => 1, 'is_plate_recognition' => 0, 'is_plate_autocollect' => 1]]);
        Queue::assertPushed(UpdateStreamProvisioning::class);

        $this->mock(MediaServerService::class);
        $this->mock(VideoAnalyticLprService::class, function ($mock) {
            $mock->shouldReceive('cameraCreate')->once()
                ->withArgs(fn ($uid, $name, $location, $options) => $options['is_plate_autocollect'] === 0)
                ->andReturn(['ok' => true]);
        });

        $this->runLastUpdateProvisioning();
    }

    private function createPersistedStream(array $overrides = []): Stream
    {
        Queue::fake();

        return Stream::create($this->baseAttributes($overrides));
    }
}
