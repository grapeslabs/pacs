<?php

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class PresenceReportComponent extends MoonShineComponent
{
    protected string $view = 'components.presence-report';

    public function __construct(
        protected string $formHtml = '',
        protected string $generateUrl = '',
        protected string $statusUrlTpl = '',
        protected string $exportUrlTpl = '',
        protected string $exportCsvUrlTpl = '',
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'formHtml' => $this->formHtml,
            'generateUrl' => $this->generateUrl,
            'statusUrlTpl' => $this->statusUrlTpl,
            'exportUrlTpl' => $this->exportUrlTpl,
            'exportCsvUrlTpl' => $this->exportCsvUrlTpl,
        ];
    }
}
