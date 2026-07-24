@once
    <style>
        .layout-navigation.pacs-header {
            display: flex !important;
            align-items: center !important;
            gap: 16px;
            flex: 0 0 auto;
            height: 64px;
            padding: 0 20px !important;
            background: #ffffff !important;
            border-bottom: 1px solid #E9ECF5 !important;
        }
        .pacs-header-left { display: flex !important; align-items: center; gap: 0; flex: 0 1 auto; min-width: 0; }
        .pacs-header-burger { display: none; align-items: center; margin-right: 12px; }
        .pacs-header-logo { display: inline-flex; align-items: center; flex: 0 0 auto; }
        .pacs-header-logo img { height: 30px; width: auto; max-width: 130px; object-fit: contain; display: block; }
        .pacs-header-divider { width: 1px; height: 28px; background: #E5E8F0; margin-left: 12px; margin-right: 24px; flex: 0 0 auto; }
        .pacs-header-crumbs { display: flex; align-items: center; min-width: 0; overflow: hidden; }
        .pacs-header-crumbs .breadcrumbs, .pacs-header-crumbs nav { display: flex; align-items: center; }
        .pacs-crumbs { display: flex; align-items: center; gap: 8px; min-width: 0; white-space: nowrap; }
        .pacs-crumb { color: #6B7390 !important; font-size: 14px; font-weight: 600; text-decoration: none !important; overflow: hidden; text-overflow: ellipsis; }
        a.pacs-crumb:hover { color: #4338CA !important; }
        .pacs-crumb--home { display: inline-flex; align-items: center; flex: 0 0 auto; }
        .pacs-crumb--home svg { width: 18px; height: 18px; display: block; }
        .pacs-crumb-sep { display: inline-flex; align-items: center; flex: 0 0 auto; color: #A6ABC2; }
        .pacs-crumb-sep svg { width: 14px; height: 14px; display: block; }

        .pacs-header-search { flex: 0 0 auto; width: 360px; max-width: 38vw; display: flex !important; margin-left: auto; }
        .pacs-header-search > * { width: 100%; max-width: none; }

        .pacs-header-right { display: flex !important; align-items: center; gap: 14px; flex: 0 0 auto; }

        .pacs-header-lupa, .pacs-search-close, .pacs-search-backdrop { display: none; }

        @media (max-width: 1024px) {
            .layout-navigation.pacs-header { gap: 10px; padding: 0 12px !important; }
            .pacs-header-burger { display: inline-flex !important; }
            .pacs-header-divider, .pacs-header-crumbs { display: none !important; }
            .pacs-header-right { margin-left: auto; gap: 6px; }
            .pacs-header-right .pacs-profile-meta,
            .pacs-header-right .pacs-profile-caret { display: none !important; }
            .pacs-header-right .pacs-profile-trigger { padding: 4px; gap: 0; }

            .pacs-header-lupa {
                display: inline-flex; align-items: center; justify-content: center;
                width: 40px; height: 40px; border: none; background: transparent;
                color: #6B7185; border-radius: 10px; cursor: pointer;
            }
            .pacs-header-lupa:hover { background: #F1F3FB; color: #2C3142; }

            .pacs-header-search { display: none !important; }
            .pacs-header-search._open {
                display: flex !important; align-items: center; gap: 8px;
                position: fixed; top: 10px; left: 10px; right: 10px;
                width: auto; max-width: none; margin: 0; padding: 8px;
                background: #fff; border-radius: 14px;
                box-shadow: 0 14px 36px rgba(20, 23, 40, 0.18);
                z-index: 100002;
            }
            .pacs-header-search._open .pacs-search { flex: 1 1 auto; max-width: none; }
            .pacs-search-close {
                display: inline-flex; align-items: center; justify-content: center;
                width: 38px; height: 38px; flex: 0 0 auto; border: none;
                background: #F1F3F9; color: #6B7185; border-radius: 10px; cursor: pointer;
            }
            .pacs-search-backdrop { display: block; position: fixed; inset: 0; background: rgba(20, 23, 40, 0.35); z-index: 100001; }
        }
    </style>
@endonce
<div class="layout-navigation pacs-header" x-data="{ mobileSearch: false }">
    <div class="pacs-header-left">
        <div class="pacs-header-burger"><x-moonshine::layout.burger/></div>
        <a href="{{ $homeUrl }}" class="pacs-header-logo"><img src="{{ $logoUrl }}" alt="logo"></a>
        <span class="pacs-header-divider"></span>
        <div class="pacs-header-crumbs">
            <nav class="pacs-crumbs" aria-label="Хлебные крошки">
                <a href="{{ $homeUrl }}" class="pacs-crumb pacs-crumb--home" title="Главная">
                    {!! file_get_contents(public_path('icons/home.svg')) !!}
                </a>

                @if(!empty($crumbSection))
                    <span class="pacs-crumb-sep">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                    </span>
                    <span class="pacs-crumb">{{ $crumbSection }}</span>
                @endif

                @foreach($crumbs as $c)
                    <span class="pacs-crumb-sep">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                    </span>
                    @if(!$loop->last && !empty($c['url']) && $c['url'] !== '#')
                        <a href="{{ $c['url'] }}" class="pacs-crumb">{{ $c['label'] }}</a>
                    @else
                        <span class="pacs-crumb">{{ $c['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>

    <div class="pacs-search-backdrop" x-show="mobileSearch" x-cloak @click="mobileSearch = false"></div>

    <div class="pacs-header-search" :class="{ '_open': mobileSearch }">
        {!! \App\MoonShine\Components\SearchBox::make()->render() !!}
        <button type="button" class="pacs-search-close" @click="mobileSearch = false" aria-label="Закрыть">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <div class="pacs-header-right">
        <button type="button" class="pacs-header-lupa" @click="mobileSearch = !mobileSearch" aria-label="Поиск">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
        </button>
        {!! \App\MoonShine\Components\NotificationWidget::make()->render() !!}
        {!! \App\MoonShine\Components\HeaderProfile::make()->render() !!}
    </div>
</div>
