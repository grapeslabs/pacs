<?php

namespace Tests\Feature\MoonShine;

use App\Models\ExportTask;
use App\Models\User;
use App\MoonShine\Resources\KeyResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportTaskTest extends TestCase
{
    use RefreshDatabase;

    private function enqueueUrl(string $format): string
    {
        return route('export-tasks.enqueue', [
            'resource' => app(KeyResource::class)->getUriKey(),
            'format' => $format,
        ]);
    }

    public function test_enqueue_creates_a_task_and_writes_the_file_to_the_private_disk(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'moonshine')
            ->postJson($this->enqueueUrl('csv'))
            ->assertOk()
            ->assertJsonStructure(['task_id', 'status_url', 'download_url']);

        $task = ExportTask::query()->findOrFail($response->json('task_id'));

        $this->assertSame(ExportTask::STATUS_DONE, $task->status);
        $this->assertSame($user->getKey(), $task->user_id);
        $this->assertStringEndsWith('.csv', $task->path);
        $this->assertTrue(Storage::disk('exports')->exists($task->path));

        $this->actingAs($user, 'moonshine')
            ->get($response->json('status_url'))
            ->assertOk()
            ->assertJson(['status' => ExportTask::STATUS_DONE]);

        $this->actingAs($user, 'moonshine')
            ->get($response->json('download_url'))
            ->assertOk();
    }

    public function test_unknown_format_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'moonshine')
            ->postJson($this->enqueueUrl('pdf'))
            ->assertStatus(422);
    }

    public function test_another_operator_cannot_read_or_download_the_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $taskId = $this->actingAs($owner, 'moonshine')
            ->postJson($this->enqueueUrl('csv'))
            ->json('task_id');

        $this->actingAs($stranger, 'moonshine')
            ->get(route('export-tasks.status', ['task' => $taskId]))
            ->assertForbidden();

        $this->actingAs($stranger, 'moonshine')
            ->get(route('export-tasks.download', ['task' => $taskId]))
            ->assertForbidden();
    }

    public function test_anonymous_request_is_redirected_to_login(): void
    {
        $this->post($this->enqueueUrl('csv'))->assertRedirect('/login');
    }
}
