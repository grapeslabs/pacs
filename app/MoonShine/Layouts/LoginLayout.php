<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\AssetManager\Js;
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

    protected function assets(): array
    {
        return [
            ...parent::assets(),
            Js::make('https://smartcaptcha.yandexcloud.net/captcha.js')->defer()
        ];
    }

    public function build(): Layout
    {
        $css = <<<HTML
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap');

            :root {
                --pv-font: "Mulish", -apple-system, "Segoe UI", system-ui, sans-serif;
                --d-fast: 120ms;
                --d-med: 200ms;
                --ease: cubic-bezier(.2,0,0,1);
            }

            * { box-sizing: border-box; }

            html, body { margin: 0; padding: 0; }

            body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                overflow: hidden;
                background: #0E0B1E !important;
                font-family: var(--pv-font);
            }

            .pv-bg { position: fixed; inset: 0; overflow: hidden; z-index: 0; background: #0E0B1E; }
            .pv-bg::before {
                content: ""; position: absolute; inset: -30%;
                background:
                    radial-gradient(40% 40% at 25% 30%, rgba(124,92,240,.55), transparent 60%),
                    radial-gradient(35% 35% at 78% 22%, rgba(91,67,224,.5), transparent 60%),
                    radial-gradient(45% 45% at 70% 78%, rgba(240,65,56,.28), transparent 60%),
                    radial-gradient(40% 40% at 30% 80%, rgba(105,114,240,.5), transparent 60%);
                filter: blur(40px); animation: pv-swirl 18s ease-in-out infinite alternate;
            }
            @keyframes pv-swirl { 0% { transform: rotate(0deg) scale(1.05); } 100% { transform: rotate(18deg) scale(1.2); } }
            .pv-orb { position: absolute; border-radius: 50%; filter: blur(2px); opacity: .5; }
            .pv-orb.o1 { width: 14px; height: 14px; background: #B9A7FF; top: 18%; left: 20%; animation: pv-float1 9s ease-in-out infinite; }
            .pv-orb.o2 { width: 9px; height: 9px; background: #fff; top: 70%; left: 30%; animation: pv-float2 11s ease-in-out infinite; }
            .pv-orb.o3 { width: 18px; height: 18px; background: #7E92F8; top: 30%; left: 80%; animation: pv-float1 13s ease-in-out infinite; }
            .pv-orb.o4 { width: 7px; height: 7px; background: #F0A5B0; top: 80%; left: 75%; animation: pv-float2 8s ease-in-out infinite; }
            @keyframes pv-float1 { 50% { transform: translateY(-26px) translateX(12px); } }
            @keyframes pv-float2 { 50% { transform: translateY(22px) translateX(-14px); } }
            .pv-grid {
                position: absolute; inset: 0; opacity: .06;
                background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px);
                background-size: 46px 46px;
                -webkit-mask-image: radial-gradient(circle at 50% 50%, #000 30%, transparent 75%);
                mask-image: radial-gradient(circle at 50% 50%, #000 30%, transparent 75%);
            }

            .pv-card {
                position: relative; z-index: 1; width: 100%; max-width: 420px;
                padding: 42px 40px 36px; border-radius: 26px;
                background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16);
                box-shadow: 0 30px 80px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.18);
                backdrop-filter: blur(22px); -webkit-backdrop-filter: blur(22px);
            }
            .pv-card::before {
                content: ""; position: absolute; inset: -1px; border-radius: 27px; padding: 1px; pointer-events: none;
                background: linear-gradient(130deg, rgba(185,167,255,.9), rgba(255,255,255,.1) 40%, rgba(240,65,56,.5) 70%, rgba(126,146,248,.9));
                background-size: 200% 200%;
                -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                -webkit-mask-composite: xor; mask-composite: exclude; animation: pv-ring 6s linear infinite; opacity: .7;
            }
            @keyframes pv-ring { to { background-position: 200% 200%; } }

            .pv-brand { display: flex; flex-direction: column; align-items: center; gap: 16px; margin-bottom: 30px; }
            .pv-brand .mark {
                width: 62px; height: 62px; border-radius: 20px; display: flex; align-items: center; justify-content: center;
                background: linear-gradient(150deg, #8E6BF0, #5B43E0); box-shadow: 0 12px 30px rgba(91,67,224,.5); animation: pv-bob 5s ease-in-out infinite;
            }
            @keyframes pv-bob { 50% { transform: translateY(-6px); } }
            .pv-brand .mark svg { width: 34px; height: 34px; }
            .pv-brand h1 { margin: 0; color: #fff; font-weight: 800; font-size: 24px; letter-spacing: .2px; }
            .pv-brand p { margin: 6px 0 0; color: rgba(255,255,255,.62); font-weight: 600; font-size: 13.5px; text-align: center; }

            .pv-foot { margin-top: 22px; text-align: center; color: rgba(255,255,255,.5); font-weight: 600; font-size: 12.5px; }

            .pv-card form { background: transparent !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }

            .pv-card .auth-field-container { margin-bottom: 14px !important; font-family: var(--pv-font) !important; }
            .pv-card .auth-label { color: rgba(255,255,255,.7) !important; font-weight: 600 !important; font-size: 12px !important; margin-bottom: 7px !important; }
            .pv-card .auth-input {
                height: 50px !important; padding: 0 16px !important; border-radius: 13px !important;
                border: 1.5px solid rgba(255,255,255,.18) !important; background: rgba(255,255,255,.06) !important;
                color: #fff !important; font-family: var(--pv-font) !important; font-weight: 600 !important; font-size: 14.5px !important;
                transition: border-color var(--d-fast), box-shadow var(--d-fast), background var(--d-fast) !important;
            }
            .pv-card .auth-input::placeholder { color: rgba(255,255,255,.4) !important; }
            .pv-card .auth-input:focus { border-color: #B9A7FF !important; background: rgba(255,255,255,.1) !important; box-shadow: 0 0 0 4px rgba(142,107,240,.25) !important; }
            .pv-card .auth-input.is-invalid { border-color: #FF8A8A !important; box-shadow: 0 0 0 4px rgba(240,65,56,.22) !important; }
            .pv-card .auth-eye-btn { color: rgba(255,255,255,.55) !important; }
            .pv-card .auth-eye-btn:hover { color: #fff !important; }
            .pv-card .auth-error-text { color: #FFB1B1 !important; font-weight: 600 !important; font-size: 12px !important; }

            .pv-card .form-group:has(.form-switcher) { margin: 6px 0 8px !important; }
            .pv-card .form-group:has(.form-switcher) label,
            .pv-card .form-group:has(.form-switcher) .form-label,
            .pv-card .form-group:has(.form-switcher) span { color: rgba(255,255,255,.8) !important; }
            .pv-card label.form-switcher { gap: 9px; }
            .pv-card .form-switcher-toggler {
                background: rgba(255,255,255,.2) !important;
            }
            .pv-card .form-switcher .peer:checked + .form-switcher-toggler {
                background: linear-gradient(180deg, #8E6BF0, #6972F0) !important;
            }

            .pv-card form button[type="submit"] {
                position: relative; width: 100% !important; height: 52px !important; border: 0 !important;
                border-radius: 99px !important; cursor: pointer; overflow: hidden;
                font-family: var(--pv-font) !important; font-weight: 700 !important; font-size: 15px !important; color: #fff !important;
                background: linear-gradient(180deg, #7E92F8, #6972F0) !important;
                box-shadow: 0 14px 30px rgba(105,114,240,.45) !important;
                transition: transform var(--d-fast), filter var(--d-fast) !important;
                margin-top: 0px !important;
            }
            .pv-card form button[type="submit"]:not(:disabled):hover { filter: brightness(1.07); transform: translateY(-1px); }
            .pv-card form button[type="submit"]:not(:disabled):active { transform: translateY(0); }

            .pv-card .smart-captcha { display: flex; justify-content: center; margin: 0; }

            .alert-error { display: none !important; }
        </style>
        HTML;

        $brand = <<<HTML
        <div class="pv-brand">
            <span class="mark">
                <svg viewBox="325 206 66 59" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="336.2" cy="217.4" r="8.8"/>
                    <circle cx="347.1" cy="235.6" r="8.8"/>
                    <circle cx="368.9" cy="235.6" r="8.8"/>
                    <circle cx="358" cy="253.8" r="8.8"/>
                    <circle cx="358" cy="217.4" r="8.8"/>
                    <circle cx="379.9" cy="217.4" r="8.8"/>
                </svg>
            </span>
            <div style="text-align:center">
                <h1>Грейпс Пакс</h1>
                <p>Система контроля и управления доступом</p>
            </div>
        </div>
        HTML;

        return Layout::make([
            Html::make([
                $this->getHeadComponent(),
                FlexibleRender::make($css),

                Body::make([
                    FlexibleRender::make('<div class="pv-bg"><div class="pv-grid"></div><span class="pv-orb o1"></span><span class="pv-orb o2"></span><span class="pv-orb o3"></span><span class="pv-orb o4"></span></div>'),

                    Div::make([
                        FlexibleRender::make($brand),
                        Components::make($this->getPage()->getComponents()),
                        FlexibleRender::make('<div class="pv-foot">© 2026 Грейпс Пакс</div>'),
                        ...$this->getPushedComponents(),
                    ])->class('pv-card'),
                ]),
            ])
                ->customAttributes(['lang' => $this->getHeadLang()])
                ->withAlpineJs()
        ]);
    }
}
