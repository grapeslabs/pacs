<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Storage;
use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DriveCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Config::set('services.va.enabled', true);
    }

    private function stream(array $overrides = []): Stream
    {
        return Model::withoutEvents(function () use ($overrides) {
            $storage = Storage::create(['name' => 'Test storage', 'type' => 'ftp', 'data' => []]);

            return Stream::query()->create(array_merge([
                'uid' => 'cam-drive',
                'storage_id' => $storage->id,
                'name' => 'Камера у входа',
                'location' => 'Проходная',
                'rtsp' => 'rtsp://127.0.0.1/stream',
                'archive_time' => 24,
                'is_active' => true,
                'va_options' => ['global_enable' => true, 'is_face_recognition' => true],
            ], $overrides));
        });
    }

    public function test_resume_returns_the_camera_to_analytics_with_correct_arguments(): void
    {
        $stream = $this->stream();

        Setting::query()->create(['key' => 'drive_limit', 'name' => 'Порог диска', 'value' => '1']);
        Cache::forever('drive_limit_stoped', true);

        $this->mock(MediaServerService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('resumeStream')->once()->with($stream->uid);
        });

        $this->mock(VideoAnalyticService::class, function ($mock) use ($stream) {
            $mock->shouldReceive('cameraCreate')
                ->once()
                ->with(
                    $stream->uid,
                    'Камера у входа',
                    'Проходная',
                    ['global_enable' => true, 'is_face_recognition' => true],
                );
        });

        $this->artisan('drive:check')->assertSuccessful();

        $this->assertFalse(Cache::get('drive_limit_stoped'));
    }

    public function test_inactive_camera_is_not_resumed(): void
    {
        $this->stream(['is_active' => false]);

        Setting::query()->create(['key' => 'drive_limit', 'name' => 'Порог диска', 'value' => '1']);
        Cache::forever('drive_limit_stoped', true);

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldNotReceive('resumeStream');
        });

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldNotReceive('cameraCreate');
        });

        $this->artisan('drive:check')->assertSuccessful();
    }

    public function test_analytics_is_not_touched_when_the_service_is_disabled(): void
    {
        Config::set('services.va.enabled', false);

        $this->stream();

        Setting::query()->create(['key' => 'drive_limit', 'name' => 'Порог диска', 'value' => '1']);
        Cache::forever('drive_limit_stoped', true);

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('resumeStream')->once();
        });

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldNotReceive('cameraCreate');
        });

        $this->artisan('drive:check')->assertSuccessful();
    }

    public function test_threshold_is_off_until_an_operator_sets_it(): void
    {
        $this->stream();

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldNotReceive('pauseStream');
            $mock->shouldNotReceive('resumeStream');
        });

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldNotReceive('cameraDelete');
            $mock->shouldNotReceive('cameraCreate');
        });

        $this->artisan('drive:check')->assertSuccessful();

        $this->assertFalse(Cache::get('drive_limit_stoped', false));
    }

    public function test_lock_is_released_even_when_the_check_throws(): void
    {
        Setting::query()->create(['key' => 'drive_limit', 'name' => 'Порог диска', 'value' => '1']);
        Cache::forever('drive_limit_stoped', true);

        $this->stream();

        $this->mock(MediaServerService::class, function ($mock) {
            $mock->shouldReceive('resumeStream')->andThrow(new \RuntimeException('медиасервер недоступен'));
        });

        $this->mock(VideoAnalyticService::class, fn ($mock) => $mock);

        try {
            $this->artisan('drive:check');
        } catch (\RuntimeException) {
        }

        $this->assertTrue(Cache::lock('drive_limit_stoped', 600)->get(), 'Лок должен был освободиться');
    }
}
