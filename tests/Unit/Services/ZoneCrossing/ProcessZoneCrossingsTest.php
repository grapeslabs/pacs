<?php

namespace Tests\Unit\Services\ZoneCrossing;

use App\Console\Commands\ProcessZoneCrossings;
use App\Models\Stream;
use Tests\TestCase;

class ProcessZoneCrossingsTest extends TestCase
{
    private function stream(): Stream
    {
        $stream = new Stream();
        $stream->name = 'Камера у входа';
        $stream->location = 'Холл';

        return $stream;
    }

    public function test_fields_mode_message(): void
    {
        $message = ProcessZoneCrossings::buildMessage(
            $this->stream(),
            ['date' => true, 'time' => true, 'stream' => true],
            '2026-01-01 10:20:30',
        );

        $this->assertSame(
            "Пересечение линии!\nДата: 01.01.2026\nВремя: 10:20:30\nВидеопоток: Камера у входа | Холл",
            $message,
        );
    }

    public function test_template_mode_message(): void
    {
        $message = ProcessZoneCrossings::buildMessage(
            $this->stream(),
            ['mode' => 'template', 'template' => '{Дата} {Время} ({Видеопоток})'],
            '2026-01-01 10:20:30',
        );

        $this->assertSame('01.01.2026 10:20:30 (Камера у входа | Холл)', $message);
    }

    public function test_person_flags_are_ignored(): void
    {
        $message = ProcessZoneCrossings::buildMessage(
            $this->stream(),
            ['lfm' => true, 'person_id' => true, 'comment' => true, 'photobank' => true],
            '2026-01-01 10:20:30',
        );

        $this->assertSame('Пересечение линии!', $message);
    }
}
