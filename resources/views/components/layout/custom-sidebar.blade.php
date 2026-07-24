@once
    <style>
        .pacs-sidebar { position: relative; display: flex; flex-direction: column; flex: 1 1 auto; height: auto; min-height: 0; color: #C9C8D8; }
        .pacs-sidebar-nav {
            flex: 1 1 auto; min-height: 0;
            overflow-y: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain;
            padding: 14px 12px 12px;
            scrollbar-width: thin; scrollbar-color: #3A3856 transparent;
        }
        .pacs-sidebar-nav::-webkit-scrollbar { width: 6px; }
        .pacs-sidebar-nav::-webkit-scrollbar-thumb { background: #3A3856; border-radius: 10px; }
        .pacs-nav-section { margin-top: 14px; }
        .pacs-nav-section-head { display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; }
        .pacs-nav-section-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #74738C; }
        .pacs-nav-section-chevron { color: #5A5972; flex: 0 0 auto; }
        .pacs-nav-item {
            position: relative;
            display: flex; align-items: center; gap: 12px; padding: 10px 12px; margin: 2px 0;
            border-radius: 10px; color: rgba(255,255,255,.82) !important; text-decoration: none !important;
            transition: background-color .15s ease, color .15s ease;
        }
        .pacs-nav-item:hover { background-color: rgba(142,107,240,.12); color: #FFFFFF !important; }
        .pacs-nav-item:active { background-color: #26243A; color: #FFFFFF !important; transform: scale(0.985); }
        .pacs-nav-ico { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; flex: 0 0 auto; color: rgba(255,255,255,.66); }
        .pacs-nav-item:hover .pacs-nav-ico, .pacs-nav-item:active .pacs-nav-ico { color: #FFFFFF; }
        .pacs-nav-text { flex: 1 1 auto; font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pacs-nav-badge { flex: 0 0 auto; min-width: 26px; text-align: center; padding: 2px 8px; border-radius: 999px; background: #2F2D43; color: rgba(255,255,255,.7); font-size: 12px; font-weight: 600; }
        .pacs-nav-item._is-active { background: rgba(142,107,240,.18); color: #FFFFFF !important; box-shadow: inset 0 0 0 1px rgba(142,107,240,.34); }
        .pacs-nav-item._is-active::before {
            content: ""; position: absolute; left: -12px; top: 7px; bottom: 7px; width: 3px;
            border-radius: 0 3px 3px 0; background: linear-gradient(180deg, #9B7DF5, #6972F0);
        }
        .pacs-nav-item._is-active .pacs-nav-ico { color: #B49CF7; }
        .pacs-nav-item._is-active .pacs-nav-badge { background: rgba(155,125,245,.32); color: #fff; }
        .pacs-sidebar-foot { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px 20px; border-top: 1px solid #2B2940; }
        .pacs-sidebar-version { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #6E6D85; }
        .pacs-sidebar-version b { color: #9C9BB4; font-weight: 700; }
        .pacs-status-dot { width: 8px; height: 8px; border-radius: 50%; background: #34D399; box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18); flex: 0 0 auto; }
        .pacs-sidebar-collapse { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; background: #2A2940; color: #9C9BB4; border: none; cursor: pointer; transition: background-color .15s ease; }
        .pacs-sidebar-collapse:hover { background: #34324E; color: #fff; }
        .pacs-sidebar-close { display: none; }
        @media (max-width: 1024px) {
            .pacs-sidebar-close {
                display: inline-flex; align-items: center; justify-content: center;
                position: absolute; top: 12px; right: 12px; z-index: 2;
                width: 40px; height: 40px; border-radius: 10px;
                background: #2A2940; color: #C9C8D8; border: none; cursor: pointer;
            }
            .pacs-sidebar-close:active { background: #34324E; }
            .pacs-sidebar-nav { padding-top: 60px; }
        }
        @media (min-width: 1025px) {
            .pacs-sidebar._collapsed .pacs-nav-text,
            .pacs-sidebar._collapsed .pacs-nav-badge,
            .pacs-sidebar._collapsed .pacs-nav-section-head,
            .pacs-sidebar._collapsed .pacs-sidebar-version { display: none; }
            .pacs-sidebar._collapsed .pacs-nav-section { margin-top: 8px; }
            .pacs-sidebar._collapsed .pacs-nav-item { justify-content: center; gap: 0; padding: 10px; }
            .pacs-sidebar._collapsed .pacs-sidebar-foot { justify-content: center; }
            .pacs-sidebar._collapsed .pacs-sidebar-collapse svg { transform: rotate(180deg); }
        }
    </style>
@endonce
<div class="pacs-sidebar" x-data="{ collapsed: $persist(false).as('pacs_sidebar_collapsed') }"
     :class="{ '_collapsed': collapsed }">

    <button type="button" class="pacs-sidebar-close" @click="asideMenuOpen = false" aria-label="Закрыть меню">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18"/>
        </svg>
    </button>

    <nav class="pacs-sidebar-nav">
        <a href="{{ $home['url'] }}"
           class="pacs-nav-item @if($home['active']) _is-active @endif">
            <span class="pacs-nav-ico">{!! $home['icon'] !!}</span>
            <span class="pacs-nav-text">{{ $home['label'] }}</span>
        </a>

        @foreach($sections as $section)
            <div class="pacs-nav-section">
                <div class="pacs-nav-section-head">
                    <span class="pacs-nav-section-title">{{ $section['label'] }}</span>
                    <svg class="pacs-nav-section-chevron" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>

                @foreach($section['items'] as $item)
                    <a href="{{ $item['url'] }}"
                       class="pacs-nav-item @if($item['active']) _is-active @endif">
                        <span class="pacs-nav-ico">{!! $item['icon'] !!}</span>
                        <span class="pacs-nav-text">{{ $item['label'] }}</span>
                        @if(! is_null($item['badge']))
                            <span class="pacs-nav-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="pacs-sidebar-foot">
        <div class="pacs-sidebar-version">
            <span class="pacs-status-dot"></span>
            <span>Грейпс Пакс · <b>v{{ $version }}</b></span>
        </div>
        <button type="button" class="pacs-sidebar-collapse" @click="collapsed = !collapsed" aria-label="Свернуть меню">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 6-6 6 6 6"/>
            </svg>
        </button>
    </div>
</div>
