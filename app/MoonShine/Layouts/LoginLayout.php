<?php

declare(strict_types=1);

namespace App\Moonshine\Layouts;

use MoonShine\Laravel\Layouts\BaseLayout;
use MoonShine\Laravel\Traits\WithComponentsPusher;
use MoonShine\UI\Components\Components;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Layout\{Body, Div, Html, Layout};

final class LoginLayout extends BaseLayout
{
    use WithComponentsPusher;

    protected ?string $title = null;
    protected ?string $description = null;
    protected function isAlwaysDark(): bool
    {
        return false;
    }

    public function build(): Layout
    {
        $css = <<<HTML
        <style>
            html, body { margin: 0; padding: 0; height: 100vh; overflow: hidden; }

            body { background-color: #e1e4fb !important; }

            .split-screen {
                display: flex;
                width: 100vw;
                height: 100vh;
            }
            .left-panel {
                display: none;
                width: 50%;
                background-color: #5d35eb;
                background-image: url('/images/LoginPlaceholder.png');
                background-size: cover;
                background-position: center;
                border-radius: 1.5rem;
                margin: 1rem;
                padding: 5rem;
                flex-direction: column;
                justify-content: space-between;
                position: relative;
            }
            .right-pane {
                display: flex;
                width: 100%;
                height: 100%;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow-y: auto;
            }
            @media (min-width: 1024px) {
                .left-panel { display: flex; }
                .right-pane { width: 50%; }
            }
            .form-container {
                width: 100%;
                max-width: 420px;
                padding: 2rem;
            }
            .title { color: white; font-size: 2.5rem; font-weight: bold; margin-top: 2rem; line-height: 1.2; }
            .subtitle { color: rgba(255,255,255,0.9); font-size: 1.75rem; font-weight: 300; line-height: 1.5; }
            .custom-form { background: transparent !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
            .custom-form button[type="submit"] {
                background-color: #7A8AF1 !important;
                border: none !important;
                border-radius: 9999px !important;
                color: white !important;
                font-weight: 600 !important;
                width: 100%;
            }
            .custom-form button[type="submit"]:hover { background-color: #6366f1 !important; }
            .alert-error {display: none !important;}
        </style>
        HTML;

        return Layout::make([
            Html::make([
                $this->getHeadComponent(),
                FlexibleRender::make($css),

                Body::make([
                    Div::make([
                        Div::make([
                            Div::make([
                                FlexibleRender::make('
                                    <svg width="62" height="62" viewBox="0 0 62 62" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M46.5381 0C55.0775 0 62 6.90885 62 15.4307C62 23.9281 55.1171 30.8201 46.6113 30.8594C46.6118 30.9065 46.6133 30.9537 46.6133 31.001C46.6133 31.0475 46.6117 31.0942 46.6113 31.1406C55.1171 31.1799 62 38.0738 62 46.5713C61.9999 55.093 55.0774 62.001 46.5381 62.001C37.9989 62.0009 31.0763 55.0929 31.0762 46.5713C31.0762 46.5427 31.077 46.5139 31.0771 46.4854L31.0752 46.4863C31.0241 46.4863 30.9729 46.4849 30.9219 46.4844C30.922 46.5133 30.9238 46.5424 30.9238 46.5713C30.9237 55.093 24.0012 62.001 15.4619 62.001C6.92263 62.001 0.000141003 55.093 0 46.5713C0 38.0495 6.92254 31.1406 15.4619 31.1406C15.4873 31.1406 15.5127 31.1405 15.5381 31.1406C15.5377 31.0942 15.5361 31.0475 15.5361 31.001C15.5361 30.9537 15.5377 30.9065 15.5381 30.8594C15.5127 30.8595 15.4873 30.8604 15.4619 30.8604C6.92257 30.8604 4.41013e-05 23.9524 0 15.4307C0 6.90885 6.92254 0 15.4619 0C24.0013 4.11464e-06 30.9238 6.90886 30.9238 15.4307C30.9238 15.4597 30.922 15.4886 30.9219 15.5176C30.9729 15.5171 31.0241 15.5166 31.0752 15.5166H31.0771C31.077 15.488 31.0762 15.4593 31.0762 15.4307C31.0762 6.90894 37.9988 0.000133732 46.5381 0Z" fill="white"/>
                                    </svg>
                                '),
                                FlexibleRender::make('<div class="title">Добро пожаловать в Pacs!</div>'),
                            ]),

                            Div::make([
                                FlexibleRender::make('<div class="subtitle">Поможем надежно организовать систему контроля<br>и управления доступом (СКУД) на вашем предприятии</div>'),
                            ]),
                        ])->class('left-panel'),
                        Div::make([
                            Div::make([
                                Div::make([
                                    $this->getLogoComponent(),
                                ])->style('display: flex; justify-content: center; margin-bottom: 2.5rem;'),
                                Components::make($this->getPage()->getComponents()),
                            ])->class('form-container'),
                            ...$this->getPushedComponents(),
                        ])->class('right-pane'),

                    ])->class('split-screen'),
                ]),
            ])
                ->customAttributes(['lang' => $this->getHeadLang()])
                ->withAlpineJs()
        ]);
    }
}
