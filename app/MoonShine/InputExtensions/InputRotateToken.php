<?php

declare(strict_types=1);

namespace App\MoonShine\InputExtensions;

use MoonShine\UI\InputExtensions\InputExtension;

final class InputRotateToken extends InputExtension
{
    protected string $view = 'fields.input-extensions.rotate-token';

    protected array $xData = [
        'rotatingToken: false',
        <<<'JS'
          async rotateToken(url) {
            if (this.rotatingToken) {
              return
            }

            this.rotatingToken = true

            try {
              const { data } = await axios.post(url)
              $refs.extensionInput.value = data.token
              MoonShine.ui.toast(data.message ?? 'Токен обновлён', 'success')
            } catch (e) {
              MoonShine.ui.toast(
                e.response?.data?.message ?? 'Не удалось получить новый токен',
                'error'
              )
            } finally {
              this.rotatingToken = false
            }
          }
        JS,
    ];
}
