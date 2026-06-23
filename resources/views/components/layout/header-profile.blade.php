@once
    <style>
        .pacs-profile { position: relative; }
        .pacs-profile-trigger { display: flex; align-items: center; gap: 10px; padding: 5px 8px; border: none; background: transparent; border-radius: 10px; cursor: pointer; transition: background-color .15s ease; }
        .pacs-profile-trigger:hover { background: #F1F3FB; }
        .pacs-profile-avatar { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; flex: 0 0 auto; }
        .pacs-profile-meta { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
        .pacs-profile-name { font-size: 14px; font-weight: 700; color: #1F2433; }
        .pacs-profile-role { font-size: 12px; color: #8A90A6; }
        .pacs-profile-caret { color: #8A90A6; transition: transform .15s ease; }
        .pacs-profile-caret._open { transform: rotate(180deg); }
        .pacs-profile-dropdown { position: absolute; right: 0; top: calc(100% + 10px); width: 270px; background: #ffffff; border-radius: 14px; box-shadow: 0 12px 32px rgba(20, 23, 40, 0.14); border: 1px solid #EDEFF6; padding: 8px; z-index: 100000; }
        .pacs-profile-card { display: flex; align-items: center; gap: 12px; padding: 10px; }
        .pacs-profile-card-avatar { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; }
        .pacs-profile-card-info { min-width: 0; }
        .pacs-profile-card-top { display: flex; align-items: center; gap: 8px; }
        .pacs-profile-card-name { font-size: 14px; font-weight: 700; color: #1F2433; }
        .pacs-profile-badge { font-size: 11px; font-weight: 600; color: #5B53E0; background: #ECEBFD; padding: 2px 8px; border-radius: 999px; }
        .pacs-profile-card-email { font-size: 12px; color: #8A90A6; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; }
        .pacs-profile-menu { padding: 6px 0; border-top: 1px solid #EFF1F7; margin-top: 4px; }
        .pacs-profile-link { display: flex; align-items: center; gap: 12px; padding: 10px 10px; border-radius: 10px; color: #2C3142 !important; text-decoration: none !important; font-size: 14px; font-weight: 500; transition: background-color .12s ease; }
        .pacs-profile-link:hover { background: #F4F6FC; }
        .pacs-profile-link svg { color: #8A90A6; flex: 0 0 auto; }
        .pacs-profile-link._logout { color: #EF4444 !important; }
        .pacs-profile-link._logout svg { color: #EF4444; }
        @media (max-width: 1024px) {
            .pacs-profile-dropdown {
                position: fixed; top: 64px; left: 8px; right: 8px;
                width: auto; max-width: none;
            }
        }
    </style>
@endonce
<div class="pacs-profile" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" class="pacs-profile-trigger" @click="open = !open">
        <img class="pacs-profile-avatar" src="{{ $avatar }}" alt="avatar">
        <span class="pacs-profile-meta">
            <span class="pacs-profile-name">{{ $userName }}</span>
            @if($role !== '')
                <span class="pacs-profile-role">{{ $role }}</span>
            @endif
        </span>
        <svg class="pacs-profile-caret" width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
             :class="{ '_open': open }" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div class="pacs-profile-dropdown" x-show="open" x-transition x-cloak>
        <div class="pacs-profile-card">
            <img class="pacs-profile-card-avatar" src="{{ $avatar }}" alt="avatar">
            <div class="pacs-profile-card-info">
                <div class="pacs-profile-card-top">
                    <span class="pacs-profile-card-name">{{ $userName }}</span>
                    @if($role !== '')
                        <span class="pacs-profile-badge">{{ $role }}</span>
                    @endif
                </div>
                @if($email !== '')
                    <div class="pacs-profile-card-email">{{ $email }}</div>
                @endif
            </div>
        </div>

        <div class="pacs-profile-menu">
            <a href="{{ $profileUrl }}" class="pacs-profile-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>
                </svg>
                <span>Мой профиль</span>
            </a>
            <a href="{{ $instructionUrl }}" class="pacs-profile-link" target="_blank" rel="noopener">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/><path d="M9 12h6M9 16h4"/>
                </svg>
                <span>Инструкция</span>
            </a>
        </div>

        <div class="pacs-profile-menu _danger">
            <a href="{{ $logoutUrl }}" class="pacs-profile-link _logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 12H3"/><path d="m6 8-4 4 4 4"/>
                </svg>
                <span>Выйти</span>
            </a>
        </div>
    </div>
</div>
