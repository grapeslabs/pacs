<?php

namespace Tests\Unit\Services\PhotoArchive;

use App\Models\Person;
use App\Services\PhotoArchive\PersonPhotoImportService;
use App\Services\VideoAnalyticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class PersonPhotoImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->mock(VideoAnalyticService::class, function ($mock) {
            $mock->shouldReceive('personCreate')->andReturn([]);
            $mock->shouldReceive('personDelete')->andReturn([]);
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    private function service(): PersonPhotoImportService
    {
        return new PersonPhotoImportService();
    }

    private function sourceFile(string $contents = 'jpeg-bytes'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_full_name_filename_creates_the_person_and_stores_the_photo(): void
    {
        $this->service()->importPhoto('Иванов_Иван_Иванович.jpg', $this->sourceFile());

        $person = Person::where('last_name', 'Иванов')->firstOrFail();
        $this->assertSame('Иван', $person->first_name);
        $this->assertSame('Иванович', $person->middle_name);
        $this->assertCount(1, $person->photo);
        $this->assertStringStartsWith('person/photos/', $person->photo[0]);
        $this->assertStringEndsWith('.jpg', $person->photo[0]);
        Storage::disk('public')->assertExists($person->photo[0]);
    }

    public function test_stored_file_keeps_the_original_bytes(): void
    {
        $this->service()->importPhoto('Петров_Пётр.png', $this->sourceFile('png-bytes'));

        $person = Person::where('last_name', 'Петров')->firstOrFail();

        $this->assertSame('png-bytes', Storage::disk('public')->get($person->photo[0]));
        $this->assertStringEndsWith('.png', $person->photo[0]);
    }

    public function test_missing_middle_name_is_stored_as_null(): void
    {
        $this->service()->importPhoto('Сидоров_Сидор.jpg', $this->sourceFile());

        $person = Person::where('last_name', 'Сидоров')->firstOrFail();

        $this->assertNull($person->middle_name);
    }

    public function test_numeric_suffix_is_stripped_and_photos_accumulate_on_one_person(): void
    {
        $service = $this->service();

        $service->importPhoto('Иванов_Иван_Иванович_1.jpg', $this->sourceFile('a'));
        $service->importPhoto('Иванов_Иван_Иванович_2.jpg', $this->sourceFile('b'));

        $people = Person::where('last_name', 'Иванов')->get();
        $this->assertCount(1, $people, 'обе фотографии должны лечь на одну персону');
        $this->assertCount(2, $people->first()->photo);
    }

    public function test_filename_with_fewer_than_two_parts_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('имя файла должно содержать минимум фамилию и имя');

        $this->service()->importPhoto('Иванов.jpg', $this->sourceFile());
    }

    public function test_rejected_filename_creates_no_person_and_no_file(): void
    {
        try {
            $this->service()->importPhoto('Иванов.jpg', $this->sourceFile());
        } catch (InvalidArgumentException) {
        }

        $this->assertDatabaseMissing('person', ['last_name' => 'Иванов']);
        $this->assertEmpty(Storage::disk('public')->allFiles('person/photos'));
    }

    public function test_two_part_name_with_a_numeric_tail_turns_the_number_into_a_first_name(): void
    {
        $this->service()->importPhoto('Иванов_1.jpg', $this->sourceFile());

        $person = Person::where('last_name', 'Иванов')->firstOrFail();

        $this->assertSame('1', $person->first_name);
    }

    public function test_soft_deleted_namesake_is_not_reused(): void
    {
        $deleted = Person::create(['last_name' => 'Удалённый', 'first_name' => 'Иван', 'middle_name' => null]);
        $deleted->delete();

        $this->service()->importPhoto('Удалённый_Иван.jpg', $this->sourceFile());

        $this->assertCount(1, Person::where('last_name', 'Удалённый')->get());
        $this->assertNotSame($deleted->id, Person::where('last_name', 'Удалённый')->first()->id);
    }

    public function test_each_import_gets_a_unique_storage_path(): void
    {
        $service = $this->service();

        $service->importPhoto('Иванов_Иван_Иванович_1.jpg', $this->sourceFile('a'));
        $service->importPhoto('Иванов_Иван_Иванович_2.jpg', $this->sourceFile('b'));

        $photos = Person::where('last_name', 'Иванов')->firstOrFail()->photo;

        $this->assertSame($photos, array_unique($photos));
        $this->assertSame('a', Storage::disk('public')->get($photos[0]));
        $this->assertSame('b', Storage::disk('public')->get($photos[1]));
    }
}
