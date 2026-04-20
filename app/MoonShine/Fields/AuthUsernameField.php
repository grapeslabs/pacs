<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Text;

final class AuthUsernameField extends Text
{
    protected string $view = 'fields.auth-username';

    protected function viewData(): array
    {
        return [
            'element' => $this
        ];
    }
}
