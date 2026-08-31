@php
    $data = $data ?? [];
    $hero = $data['hero'] ?? [];
    $sections = $data['sections'] ?? [];
    $footer = $data['footer'] ?? [];

    $toneStyles = [
        'ok'     => 'background:#C9F3CE;color:#36944C;',
        'warn'   => 'background:#FFF7DF;color:#D47F00;',
        'danger' => 'background:#FAD9D5;color:#C2322B;',
        'muted'  => 'background:#EAEDF8;color:#66667D;',
    ];
@endphp

<div class="ds-card">
    <div class="ds-card__head">
        <button type="button" class="ds-card__back" x-show="canGoBack" x-cloak @click="back()" aria-label="Назад">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div style="min-width:0; flex:1;">
            <h3 class="ds-card__title">{{ $data['title'] ?? 'Карточка' }}</h3>
            @if(!empty($data['subtitle']))
                <p class="ds-card__sub">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <button type="button" class="ds-card__close"
                @click="$dispatch('modal-toggled', { id: 'detail-sidebar' })" aria-label="Закрыть">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
    </div>

    <div class="ds-card__body">
        {{-- HERO --}}
        <div class="ds-hero">
            <div class="ds-hero__text">
                <div class="ds-hero__name">{{ $hero['title'] ?? '' }}</div>
                @if(!empty($hero['subtitle']))
                    <div class="ds-hero__sub">{{ $hero['subtitle'] }}</div>
                @endif

                @if(!empty($hero['plate']))
                    <span class="ds-plate">
                        <span class="ds-plate__main">{{ $hero['plate']['main'] ?? '' }}</span>
                        @if(!empty($hero['plate']['region']))
                            <span class="ds-plate__reg">
                                <span class="ds-plate__num">{{ $hero['plate']['region'] }}</span>
                                <span class="ds-plate__rus">
                                    RUS
                                    <span class="ds-plate__flag"><i></i><i></i><i></i></span>
                                </span>
                            </span>
                        @endif
                    </span>
                @endif

                @if(!empty($hero['tags']))
                    <div class="ds-hero__tags">
                        @foreach($hero['tags'] as $t)
                            <span class="ds-hero__tag">{{ $t }}</span>
                        @endforeach
                    </div>
                @endif

                @if(!empty($hero['badges']))
                    <div class="ds-hero__badges">
                        @foreach($hero['badges'] as $b)
                            @php $bTag = !empty($b['url']) ? 'a' : 'span'; @endphp
                            <{{ $bTag }} class="ds-hero__badge{{ (!empty($b['chevron']) || !empty($b['url'])) ? ' ds-hero__badge--link' : '' }}"
                                @if(!empty($b['url'])) href="{{ $b['url'] }}" @endif>
                                @if(!empty($b['icon'])) <span class="ds-ic">{!! $b['icon'] !!}</span> @endif
                                {{ $b['text'] ?? '' }}
                                @if(!empty($b['chevron']) || !empty($b['url']))
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @endif
                            </{{ $bTag }}>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="ds-hero__media">
                @if(!empty($hero['avatar']))
                    <img class="ds-hero__avatar ds-glow" src="{{ $hero['avatar'] }}" alt="">
                @elseif(!empty($hero['icon']))
                    <span class="ds-hero__icon ds-glow">{!! $hero['icon'] !!}</span>
                @elseif(!empty($hero['initials']))
                    <span class="ds-hero__initials ds-glow">{{ $hero['initials'] }}</span>
                @endif
            </div>
        </div>

        {{-- BANNER (напр. предупреждение о заморозке) --}}
        @if(!empty($data['banner']))
            @php
                $banners = isset($data['banner']['text']) ? [$data['banner']] : $data['banner'];
                $bannerTones = [
                    'ok'     => 'background:#C9F3CE;color:#36944C;',
                    'warn'   => 'background:#FFF7DF;color:#946000;',
                    'danger' => 'background:#FAD9D5;color:#C2322B;',
                    'muted'  => 'background:#EAEDF8;color:#66667D;',
                ];
            @endphp
            @foreach($banners as $bn)
                <div class="ds-banner" style="{{ $bannerTones[$bn['tone'] ?? 'warn'] ?? $bannerTones['warn'] }}">
                    @if(!empty($bn['icon']))
                        <span class="ds-banner__ic">{!! $bn['icon'] !!}</span>
                    @endif
                    <span class="ds-banner__text">{{ $bn['text'] ?? '' }}</span>
                </div>
            @endforeach
        @endif

        {{-- SECTIONS --}}
        @foreach($sections as $sec)
            @php $collapsible = ! empty($sec['collapsible']); @endphp
            <div class="ds-sec" @if($collapsible) x-data="{ open: {{ !empty($sec['collapsed']) ? 'false' : 'true' }} }" @endif>
                <div class="ds-sec__head" @if($collapsible) @click="open = !open" style="cursor:pointer;" @endif>
                    @if(!empty($sec['icon'])) <span class="ds-sec__ic">{!! $sec['icon'] !!}</span> @endif
                    <span class="ds-sec__title">{{ $sec['title'] ?? '' }}</span>
                    @if($collapsible)
                        <svg class="ds-sec__chev" :style="open ? 'transform:rotate(180deg)' : ''" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </div>
                <span class="ds-sec__line"></span>

                <div @if($collapsible) x-show="open" @endif>
                @php $hasPanel = !empty($sec['fields']) || !empty($sec['text']) || !empty($sec['tags']); @endphp
                @if($hasPanel)<div class="ds-sec__panel">@endif
                @if(!empty($sec['fields']))
                    <div class="ds-fields{{ !empty($sec['single']) ? ' ds-fields--single' : '' }}">
                        @foreach($sec['fields'] as $f)
                            <div class="ds-field{{ !empty($f['full']) ? ' ds-field--full' : '' }}">
                                <div class="ds-field__label">{{ $f['label'] ?? '' }}</div>
                                <div class="ds-field__value">
                                    @if(!empty($f['value_html']))
                                        {!! $f['value_html'] !!}
                                    @else
                                        {{ ($f['value'] ?? '') === '' || $f['value'] === null ? '—' : $f['value'] }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($sec['text']))
                    <p class="ds-text">{{ $sec['text'] }}</p>
                @endif

                @if(!empty($sec['tags']))
                    <div class="ds-tags">
                        @foreach($sec['tags'] as $t)
                            <span class="ds-tag">{{ $t }}</span>
                        @endforeach
                    </div>
                @endif
                @if($hasPanel)</div>@endif

                @if(!empty($sec['items']))
                    <div class="ds-items{{ !empty($sec['itemsTight']) ? ' ds-items--tight' : '' }}">
                        @foreach($sec['items'] as $it)
                            <div class="ds-item">
                                @if(!empty($it['icon']))
                                    <span class="ds-item__ic ds-item__ic--{{ $it['iconTone'] ?? 'muted' }}">{!! $it['icon'] !!}</span>
                                @endif
                                <div style="min-width:0; flex:1;">
                                    <div class="ds-item__title">{{ $it['title'] ?? '' }}</div>
                                    @if(!empty($it['subtitle']))
                                        <div class="ds-item__sub">{{ $it['subtitle'] }}</div>
                                    @endif
                                </div>
                                <div class="ds-item__right">
                                    @if(!empty($it['badge']))
                                        <span class="ds-badge" style="{{ $toneStyles[$it['badge']['tone'] ?? 'muted'] ?? $toneStyles['muted'] }}">{{ $it['badge']['text'] ?? '' }}</span>
                                    @endif
                                    @if(!empty($it['meta']))
                                        <span class="ds-item__meta">{{ $it['meta'] }}</span>
                                    @endif
                                    @if(!empty($it['actions']))
                                        @foreach($it['actions'] as $a)
                                            {!! $a !!}
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                </div>
            </div>
        @endforeach
    </div>

    @if(!empty($footer))
        <div class="ds-card__foot">
            @foreach($footer as $btn)
                @if(!empty($btn['spacer']))
                    <span style="flex:1 1 auto;"></span>
                @elseif(!empty($btn['html']))
                    {!! $btn['html'] !!}
                @else
                    <a class="ds-btn ds-btn--{{ $btn['variant'] ?? 'secondary' }}"
                       href="{{ $btn['url'] ?? '#' }}"
                       @foreach(($btn['attrs'] ?? []) as $k => $v) {{ $k }}="{{ $v }}" @endforeach>
                        @if(!empty($btn['icon'])) <span class="ds-ic">{!! $btn['icon'] !!}</span> @endif
                        {{ $btn['text'] ?? '' }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif
</div>

@once
    <style>
        .ds-card { display:flex; flex-direction:column; height:100%; background:#fff; }
        .ds-card__head { flex:0 0 auto; display:flex; align-items:flex-start; justify-content:space-between; gap:10px; padding:20px 24px 14px; }
        .ds-card__back { flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; margin-top:-2px; border:none; background:transparent; border-radius:9px; color:#6B7185; cursor:pointer; transition:background .15s,color .15s; }
        .ds-card__back:hover { background:#F1F3FB; color:#2C3142; }
        [x-cloak] { display:none !important; }
        .ds-card__title { margin:0; font-size:19px; font-weight:800; color:#1a1a2e; line-height:1.25; }
        .ds-card__sub { margin:3px 0 0; font-size:13px; color:#8A8AA3; }
        .ds-card__close { flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border:none; background:transparent; border-radius:50%; color:#9096AE; cursor:pointer; transition:background .15s,color .15s; }
        .ds-card__close:hover { background:#F1F3FB; color:#4E4E61; }

        .ds-card__body { flex:1 1 0%; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding:6px 24px 24px; }

        /* hero */
        .ds-hero { position:relative; overflow:hidden; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px; border-radius:20px;
            background:linear-gradient(120deg,#2A2740 0%,#332B52 55%,#3A2C58 100%); color:#fff; }
        .ds-hero::before { content:""; position:absolute; right:-30px; top:-40px; width:170px; height:170px; border-radius:50%; background:radial-gradient(circle, rgba(126,146,248,.4), transparent 62%); pointer-events:none; z-index:0; }
        .ds-hero__text { position:relative; z-index:1; min-width:0; }
        .ds-hero__name { font-size:20px; font-weight:700; line-height:1.2; }
        .ds-hero__sub { margin-top:4px; font-size:13px; color:rgba(255,255,255,.72); }
        .ds-hero__badges { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .ds-hero__badge { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:9999px; background:rgba(0,0,0,.12); font-size:12px; font-weight:600; color:#fff; text-decoration:none; }
        .ds-hero__badge--link { cursor:pointer; transition:background .15s; }
        .ds-hero__badge--link:hover { background:rgba(255,255,255,.16); }
        .ds-hero__badge .ds-ic svg, .ds-hero__badge svg { width:14px; height:14px; }

        .ds-hero__tags { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .ds-hero__tag { display:inline-flex; align-items:center; padding:4px 12px; border-radius:9999px; background:rgba(0,0,0,.12); border:0; color:#fff; font-size:12px; font-weight:600; }

        /* Баннер-предупреждение (напр. заморозка) */
        .ds-banner { display:flex; align-items:flex-start; gap:12px; margin:16px 0 4px; padding:14px 16px; border-radius:14px; font-size:13.5px; font-weight:600; line-height:1.45; }
        .ds-banner__ic { flex:0 0 auto; display:flex; align-items:center; margin-top:1px; }
        .ds-banner__ic svg { width:20px; height:20px; }
        .ds-banner__text { min-width:0; }

        .ds-hero__media { position:relative; z-index:1; flex:0 0 auto; }
        .ds-glow { }
        .ds-hero__avatar { width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.25); }
        /* Иконка hero — без подложки, только на световом фоне (.ds-hero::before) */
        .ds-hero__icon { display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; background:transparent; color:#fff; }
        .ds-hero__icon svg { width:46px; height:46px; }
        .ds-hero__initials { display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:linear-gradient(180deg,#7E92F8,#6972F0); color:#fff; font-size:22px; font-weight:700; }

        /* реалистичный ГРЗ: регион над RUS */
        .ds-plate { display:inline-flex; align-items:stretch; height:36px; margin-top:12px; background:#fff; border:1.5px solid #12121c; border-radius:6px; overflow:hidden; font-family:'Arial Narrow',Arial,sans-serif; }
        .ds-plate__main { display:flex; align-items:center; padding:0 11px; font-size:19px; font-weight:700; letter-spacing:1.5px; color:#12121c; }
        .ds-plate__reg { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:0 8px; border-left:1.5px solid #12121c; }
        .ds-plate__num { font-size:16px; font-weight:700; letter-spacing:1px; color:#12121c; line-height:1; }
        .ds-plate__rus { display:flex; align-items:center; gap:3px; font-size:8px; font-weight:700; color:#12121c; letter-spacing:.5px; margin-top:1px; }
        .ds-plate__flag { display:flex; flex-direction:column; width:11px; height:7px; border:.5px solid rgba(0,0,0,.2); border-radius:1px; overflow:hidden; }
        .ds-plate__flag i { flex:1; }
        .ds-plate__flag i:nth-child(1) { background:#fff; }
        .ds-plate__flag i:nth-child(2) { background:#0039A6; }
        .ds-plate__flag i:nth-child(3) { background:#D52B1E; }

        /* section */
        .ds-sec { margin-top:16px; padding:18px; border-radius:18px; background:#F6F7FC; }
        .ds-sec__head { display:flex; align-items:center; gap:9px; margin-bottom:10px; }
        .ds-sec__ic { display:inline-flex; color:#9096AE; }
        .ds-sec__ic svg { width:16px; height:16px; }
        .ds-sec__title { font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#8A8AA3; white-space:nowrap; }
        .ds-sec__line { display:block; width:100%; height:1px; border-top:1px dashed #D9DEEE; margin:0 0 14px; }
        .ds-sec__chev { flex:0 0 auto; margin-left:auto; color:#9096AE; transition:transform .2s; }

        .ds-item__ic { flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:9px; background:#EAEDF8; color:#66667D; }
        .ds-item__ic svg { width:18px; height:18px; }
        .ds-item__ic--ok { background:#C9F3CE; color:#36944C; }
        .ds-item__ic--danger { background:#FAD9D5; color:#C2322B; }
        .ds-item__ic--warn { background:#FFF7DF; color:#D47F00; }
        .ds-item__ic--info { background:#F1F3FB; color:#7381F4; }

        /* Панель значений тянется до границ секции (margin = padding секции),
           чтобы левый край значения совпал с левым краем заголовка блока. */
        .ds-sec__panel { background:transparent; border-radius:0; padding:0; margin:0; }
        .ds-fields { display:grid; grid-template-columns:1fr 1fr; gap:16px 20px; }
        .ds-fields--single { grid-template-columns:1fr; }
        .ds-field { min-width:0; }
        .ds-field--full { grid-column:1 / -1; }
        .ds-field__label { font-size:12px; color:#9096AE; margin-bottom:3px; }
        .ds-field__value { font-size:14px; font-weight:700; color:#22223A; word-break:break-word; }

        .ds-text { margin:0; font-size:14px; line-height:1.5; color:#3B3B55; white-space:pre-line; word-break:break-word; }
        .ds-tags { display:flex; flex-wrap:wrap; gap:8px; }
        .ds-tag { display:inline-flex; align-items:center; padding:5px 12px; border-radius:4px; background:#D7DBEF; color:#4E4E61; font-size:13px; font-weight:600; }

        .ds-items { display:flex; flex-direction:column; gap:10px; }
        .ds-item { display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; background:#fff; border:1px solid #ECEEF7; }

        /* Компактный список (последние события): единая карточка с разделителями */
        .ds-items--tight { gap:0; background:#fff; border:1px solid #ECEEF7; border-radius:14px; overflow:hidden; }
        .ds-items--tight .ds-item { border:none; border-radius:0; padding:11px 14px; }
        .ds-items--tight .ds-item + .ds-item { border-top:1px solid #F0F2F8; }
        .ds-items--tight .ds-item__ic { border-radius:50%; width:30px; height:30px; }
        .ds-items--tight .ds-item__ic svg { width:17px; height:17px; }
        .ds-item__title { font-size:14px; font-weight:700; color:#22223A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ds-item__sub { font-size:12px; color:#9096AE; margin-top:2px; }
        .ds-item__right { display:flex; align-items:center; gap:8px; margin-left:auto; }
        .ds-item__meta { font-size:12px; color:#9096AE; white-space:nowrap; }
        .ds-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:9999px; font-size:12px; font-weight:600; white-space:nowrap; }
        .ds-item .ds-iconbtn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border:none; background:transparent; border-radius:8px; color:#9096AE; cursor:pointer; text-decoration:none; transition:background .15s,color .15s; }
        .ds-item .ds-iconbtn:hover { background:#F1F3FB; color:#4E4E61; }
        .ds-item .ds-iconbtn svg { width:18px; height:18px; }

        /* цветной образец (авто) */
        .ds-swatch { display:inline-flex; align-items:center; gap:8px; }
        .ds-swatch__box { width:14px; height:14px; border-radius:4px; border:1px solid rgba(0,0,0,.15); flex:0 0 14px; }

        /* footer */
        .ds-card__foot { flex:0 0 auto; display:flex; align-items:center; justify-content:flex-end; gap:12px; padding:14px 24px; border-top:1px solid #EEF0F8; }
        .ds-btn { display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border-radius:9999px; font-size:14px; font-weight:600; text-decoration:none; cursor:pointer; border:1px solid transparent; }
        .ds-btn .ds-ic svg, .ds-btn svg { width:17px; height:17px; }
        .ds-btn--primary { background:#6972F0; color:#fff; }
        .ds-btn--secondary { background:#fff; color:#4E4E61; border-color:#D7D0EF; }
        .ds-btn--danger { background:#fff; color:#C0392B; border-color:#F0C7C2; }
        .ds-btn--primary:hover { background:#5860e6; }
        .ds-btn--secondary:hover { background:#F6F7FC; }
        .ds-btn--danger:hover { background:#FCF1F0; }
        .ds-fbtn { display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; padding:0 !important; gap:0 !important; border-radius:12px; border:1px solid #D7D0EF; background:#fff; color:#4E4E61; cursor:pointer; text-decoration:none; transition:background .15s,color .15s; line-height:0; }
        .ds-fbtn:hover { background:#F6F7FC; color:#2C3142; }
        .ds-fbtn > * { margin:0 !important; display:inline-flex; align-items:center; justify-content:center; }
        .ds-fbtn svg { width:19px; height:19px; display:block; }
    </style>
@endonce
