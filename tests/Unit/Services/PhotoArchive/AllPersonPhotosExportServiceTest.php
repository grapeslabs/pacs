<?php

namespace Tests\Unit\Services\PhotoArchive;

use App\Models\Person;
use App\Services\PhotoArchive\AllPersonPhotosExportService;
use App\Services\VideoAnalyticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class AllPersonPhotosExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $archives = [];

    private string $storageDir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->storageDir = sys_get_temp_dir().'/photoexport_storage_'.uniqid();
        mkdir($this->storageDir, recursive: true);
        $this->app->useStoragePath($this->storageDir);

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('personCreate')->andReturn([]);
            $mock->shouldReceive('personDelete')->andReturn([]);
        });

    }

    protected function tearDown(): void
    {
        $this->archives = [];
        $this->removeDirectory($this->storageDir);

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

    private function export(): ?string
    {
        $path = (new AllPersonPhotosExportService())->createArchive();

        if ($path !== null) {
            $this->archives[] = $path;
        }

        return $path;
    }

    private function person(array $photos, array $attrs = []): Person
    {
        return Person::create(array_merge([
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => 'Иванович',
            'photo' => $photos,
        ], $attrs));
    }

    private function putPhoto(string $path, string $contents = 'bytes'): string
    {
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    private function entries(string $zipPath): array
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true, 'архив должен открываться');

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        sort($names);

        return $names;
    }

    private function entryContents(string $zipPath, string $entry): string
    {
        $zip = new ZipArchive();
        $zip->open($zipPath);
        $contents = $zip->getFromName($entry);
        $zip->close();

        return $contents;
    }

    public function test_no_persons_at_all_yields_no_archive(): void
    {
        $this->assertNull($this->export());
    }

    public function test_persons_without_photos_yield_no_archive(): void
    {
        $this->person([]);
        $this->person([], ['last_name' => 'Петров']);

        $this->assertNull($this->export());
    }

    public function test_empty_archive_file_is_removed_from_disk(): void
    {
        $this->person([]);

        $before = glob(storage_path('app/temp').'/persons_photos_archive_*.zip');
        $this->assertNull($this->export());
        $after = glob(storage_path('app/temp').'/persons_photos_archive_*.zip');

        $this->assertSame($before, $after);
    }

    public function test_single_photo_per_person_is_named_without_a_counter(): void
    {
        $this->person([$this->putPhoto('person/photos/a.jpg')]);
        $this->person([$this->putPhoto('person/photos/b.jpg')], [
            'last_name' => 'Петров', 'first_name' => 'Пётр', 'middle_name' => null,
        ]);

        $zip = $this->export();

        $this->assertSame(
            ['images.tsv', 'Иванов_Иван_Иванович.jpg', 'Петров_Пётр.jpg'],
            $this->entries($zip),
        );
    }

    public function test_several_photos_of_one_person_are_numbered(): void
    {
        $this->person([
            $this->putPhoto('person/photos/a.jpg'),
            $this->putPhoto('person/photos/b.png'),
        ]);

        $zip = $this->export();

        $this->assertSame(
            ['images.tsv', 'Иванов_Иван_Иванович_1.jpg', 'Иванов_Иван_Иванович_2.png'],
            $this->entries($zip),
        );
    }

    public function test_namesakes_do_not_overwrite_each_other(): void
    {
        $this->person([$this->putPhoto('person/photos/a.jpg', 'первый')]);
        $this->person([$this->putPhoto('person/photos/b.jpg', 'второй')]);

        $zip = $this->export();

        $this->assertSame(
            ['images.tsv', 'Иванов_Иван_Иванович.jpg', 'Иванов_Иван_Иванович_1.jpg'],
            $this->entries($zip),
        );
        $this->assertSame('первый', $this->entryContents($zip, 'Иванов_Иван_Иванович.jpg'));
        $this->assertSame('второй', $this->entryContents($zip, 'Иванов_Иван_Иванович_1.jpg'));
    }

    public function test_person_without_any_name_falls_back_to_its_id(): void
    {
        $person = $this->person([$this->putPhoto('person/photos/a.jpg')], [
            'last_name' => '', 'first_name' => '', 'middle_name' => null,
        ]);

        $zip = $this->export();

        $this->assertSame(['images.tsv', 'person_'.$person->id.'.jpg'], $this->entries($zip));
    }

    public function test_missing_files_are_skipped_but_still_shift_the_counter(): void
    {
        $this->person(['person/photos/gone.jpg', $this->putPhoto('person/photos/here.jpg')]);

        $zip = $this->export();

        $this->assertSame(['images.tsv', 'Иванов_Иван_Иванович_1.jpg'], $this->entries($zip));
    }

    public function test_soft_deleted_persons_are_not_exported(): void
    {
        $this->person([$this->putPhoto('person/photos/a.jpg')]);
        $removed = $this->person([$this->putPhoto('person/photos/b.jpg')], ['last_name' => 'Петров']);
        $removed->delete();

        $zip = $this->export();

        $this->assertSame(['images.tsv', 'Иванов_Иван_Иванович.jpg'], $this->entries($zip));
    }

    public function test_tsv_lists_every_archived_file(): void
    {
        $this->person([
            $this->putPhoto('person/photos/a.jpg'),
            $this->putPhoto('person/photos/b.jpg'),
        ]);

        $zip = $this->export();

        $this->assertSame(
            "person/photos/a.jpg\tИванов_Иван_Иванович_1\nperson/photos/b.jpg\tИванов_Иван_Иванович_2",
            $this->entryContents($zip, 'images.tsv'),
        );
    }

    public function test_export_loads_every_person_into_memory_at_once(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->person([$this->putPhoto("person/photos/p{$i}.jpg")], [
                'last_name' => 'Персона'.$i, 'first_name' => 'Имя', 'middle_name' => null,
            ]);
        }

        $zip = $this->export();

        $this->assertCount(6, $this->entries($zip));
    }
}
