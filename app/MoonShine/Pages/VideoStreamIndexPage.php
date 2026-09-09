<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Stream;
use App\MoonShine\Components\SearchField;
use App\MoonShine\Components\StreamGridComponent;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Flex;

class VideoStreamIndexPage extends CustomIndexPage
{
    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Видеопотоки';
    }

    public function getPageButtons(): array
    {
        $resource = $this->getResource();

        return [
            Flex::make([
                $this->streamsHeading(),

                Flex::make([
                    $this->streamsViewToggle(),
                    $this->videoWallButton(),
                    $this->getCustomCreateButton(),
                ])->itemsAlign('center'),
            ])
                ->justifyAlign('between')
                ->itemsAlign('center')
                ->class('mb-4'),

            Div::make([
                SearchField::make(placeholder: $this->buildSearchPlaceholder()),
            ])->class('mb-6')->customAttributes([
                'x-show' => "\$store.streamView && \$store.streamView.mode === 'table'",
                'x-cloak' => true,
            ]),
        ];
    }

    protected function mainLayer(): array
    {
        $resource = $this->getResource();

        $items = Stream::orderBy('created_at')->get();

        $grid = Div::make([
            StreamGridComponent::make()
                ->items($items)
                ->editUrlResolver(
                    fn ($item) => $resource->getFormPageUrl($item->getKey(), params: [
                        '_component_name' => $resource->getListComponentName(),
                        '_async_form' => true,
                    ], fragment: 'crud-form')
                )
                ->deleteUrlResolver(
                    fn ($item) => $resource->getRoute('crud.destroy', $item->getKey())
                ),
        ])->customAttributes([
            'x-show' => "\$store.streamView && \$store.streamView.mode === 'grid'",
            'x-cloak' => true,
        ]);

        $table = Div::make($this->getItemsComponents())->customAttributes([
            'x-show' => "\$store.streamView && \$store.streamView.mode === 'table'",
            'x-cloak' => true,
        ]);

        return [
            ...$this->getPageButtons(),
            ...$this->getQueryTags(),
            $grid,
            $table,
        ];
    }

    protected function getCustomCreateButton()
    {
        $resource = $this->getResource();

        if ($resource->isCreateInModal()) {
            return ActionButton::make(
                'Добавить камеру',
                fn ($item) => $resource->getFormPageUrl(params: [
                    '_component_name' => $this->getListComponentName(),
                    '_async_form' => true,
                ], fragment: 'crud-form')
            )
                ->customAttributes([
                    '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Новая камера', subtitle: 'Подключение видеопотока' })",
                ])
                ->async(selector: "#{$this->safeModalName}_content")
                ->primary()
                ->icon('plus')
                ->canSee(fn () => $resource->hasAction(Action::CREATE) && $resource->can(Ability::CREATE));
        }

        return ActionButton::make('Добавить камеру', $resource->getFormPageUrl())
            ->canSee(static fn (): bool => $resource->hasAction(Action::CREATE) && $resource->can(Ability::CREATE))
            ->primary()
            ->icon('plus');
    }

    private function streamsHeading(): string
    {
        return '<div x-data style="padding-left:20px;padding-top:4px;">'
            . '<h2 style="font-size:22px;font-weight:700;color:#1a1a2e;margin:0;line-height:1.2;">Видеопотоки</h2>'
            . '<p style="font-size:13px;color:#7F7F9D;margin:4px 0 0;" '
            . 'x-text="$store.streamView ? ($store.streamView.total + \' камер · \' + $store.streamView.active + \' активны · \' + $store.streamView.notResponding + \' не отвечают\') : \'\'"></p>'
            . '</div>';
    }

    private function videoWallButton(): string
    {
        $url = toPage(VideoWallPage::class);
        $style = 'display:inline-flex;align-items:center;gap:8px;padding:9px 18px;margin-right:14px;border-radius:9999px;background:#fff;color:#4E4E61;border:1px solid #D7D0EF;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;font-family:inherit;';
        $icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="8" height="16" rx="1.5"></rect><rect x="13" y="4" width="8" height="7" rx="1.5"></rect><rect x="13" y="13" width="8" height="7" rx="1.5"></rect></svg>';

        return '<a href="' . $url . '" style="' . $style . '">' . $icon . 'Видеостены</a>';
    }

    private function streamsViewToggle(): string
    {
        $base = 'display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;';
        $active = 'background:#fff;color:#1a1a2e;box-shadow:0 1px 2px rgba(0,0,0,.12);';
        $inactive = 'background:transparent;color:#7F7F9D;';

        $gridIcon = '<svg width=\'16\' height=\'16\' viewBox=\'0 0 20 20\' fill=\'none\'><rect x=\'2.5\' y=\'2.5\' width=\'6\' height=\'6\' rx=\'1.5\' stroke=\'currentColor\' stroke-width=\'1.5\'/><rect x=\'11.5\' y=\'2.5\' width=\'6\' height=\'6\' rx=\'1.5\' stroke=\'currentColor\' stroke-width=\'1.5\'/><rect x=\'2.5\' y=\'11.5\' width=\'6\' height=\'6\' rx=\'1.5\' stroke=\'currentColor\' stroke-width=\'1.5\'/><rect x=\'11.5\' y=\'11.5\' width=\'6\' height=\'6\' rx=\'1.5\' stroke=\'currentColor\' stroke-width=\'1.5\'/></svg>';
        $listIcon = '<svg width=\'16\' height=\'16\' viewBox=\'0 0 20 20\' fill=\'none\'><path d=\'M6 5h11M6 10h11M6 15h11\' stroke=\'currentColor\' stroke-width=\'1.5\' stroke-linecap=\'round\'/><circle cx=\'3\' cy=\'5\' r=\'1\' fill=\'currentColor\'/><circle cx=\'3\' cy=\'10\' r=\'1\' fill=\'currentColor\'/><circle cx=\'3\' cy=\'15\' r=\'1\' fill=\'currentColor\'/></svg>';

        return '<div x-data style="display:inline-flex;background:#EDEFF7;border:1px solid #E0E3F0;border-radius:10px;padding:3px;margin-right:8px;">'
            . '<button type="button" @click="$store.streamView && $store.streamView.setMode(\'grid\')" '
            . ':style="\'' . $base . '\' + (($store.streamView && $store.streamView.mode===\'grid\')?\'' . $active . '\':\'' . $inactive . '\')">' . $gridIcon . 'Сетка</button>'
            . '<button type="button" @click="$store.streamView && $store.streamView.setMode(\'table\')" '
            . ':style="\'' . $base . '\' + (($store.streamView && $store.streamView.mode===\'table\')?\'' . $active . '\':\'' . $inactive . '\')">' . $listIcon . 'Таблица</button>'
            . '</div>';
    }
}
