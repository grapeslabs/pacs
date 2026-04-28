<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Password;

final class AuthPasswordField extends Password
{
    protected string $view = 'fields.auth-password';

    protected function viewData(): array
    {
        return [
            'element' => $this
        ];
    }
}
