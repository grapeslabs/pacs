@once
    <style>
        /* Мобайл: прячем декоративную картинку */
        @media (max-width: 1024px) {
            .welcome-hero-spark { display: none !important; }
        }
    </style>
@endonce
<div {{ $attributes }}
     style="position:relative;overflow:hidden;border-radius:18px;padding:22px 24px;height:100%;box-sizing:border-box;
            background: linear-gradient(120deg,#6E63E6 0%,#7E92F8 52%,#8E6BF0 100%); color:#fff;
            display:flex;flex-direction:column;justify-content:space-between;">
    <svg class="welcome-hero-spark" viewBox="0 0 100 100" fill="none" style="position:absolute;right:56px;top:50%;transform:translateY(-50%);width:150px;height:150px;pointer-events:none;"><path d="M50 4c3 26 16 39 42 46-26 7-39 20-42 46-3-26-16-39-42-46 26-7 39-20 42-46z" fill="rgba(255,255,255,.5)"></path><path d="M84 12c1 9 5 13 14 15-9 2-13 6-14 15-1-9-5-13-14-15 9-2 13-6 14-15z" fill="rgba(255,255,255,.32)"></path></svg>
    <div style="position:relative;">
        <h1 style="margin:0;font-size:1.4rem;font-weight:600;letter-spacing:-0.01em;">
            {{ $greeting }}@if($userName), {{ $userName }}@endif
        </h1>
        <p style="margin:10px 0 0;max-width:460px;font-size:0.9rem;line-height:1.5;color:rgba(255,255,255,0.82);">
            Сегодня {{ $today }}. Система контроля доступа работает в штатном режиме — нарушений не зафиксировано.
        </p>
    </div>

    @if($streamsUrl)
        <div style="position:relative;margin-top:24px;">
            <a href="{{ $streamsUrl }}"
               style="display:inline-flex;align-items:center;gap:10px;background:#fff;color:#1b1a2a;
                      padding:6px 6px 6px 20px;border-radius:9999px;font-size:0.9rem;font-weight:600;text-decoration:none;">
                Видеопотоки
                <span style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9999px;background:linear-gradient(135deg,#7e92f8,#6e63e6);color:#fff;flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <path d="m9 6 6 6-6 6"/>
                    </svg>
                </span>
            </a>
        </div>
    @endif
</div>
