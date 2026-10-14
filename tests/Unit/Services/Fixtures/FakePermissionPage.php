<?php

namespace Tests\Unit\Services\Fixtures;

use MoonShine\Core\Pages\Page;

class FakePermissionPage extends Page
{
    protected string $title = 'Настройки';

    public function components(): iterable
    {
        return [];
    }
}
