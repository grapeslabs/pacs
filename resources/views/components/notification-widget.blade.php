@once
    <style>
        .pacs-notify { position: relative; }
        .pacs-notify-bell { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; border: none; background: transparent; color: #6B7185; cursor: pointer; transition: background-color .15s ease, color .15s ease; }
        .pacs-notify-bell:hover { background: #F1F3FB; color: #2C3142; }
        .pacs-notify-dot { position: absolute; top: 9px; right: 10px; width: 8px; height: 8px; border-radius: 50%; background: #6366F1; border: 2px solid #fff; }
        .pacs-notify-panel { position: absolute; right: 0; top: calc(100% + 10px); width: 390px; max-width: 92vw; background: #ffffff; border-radius: 16px; box-shadow: 0 14px 36px rgba(20, 23, 40, 0.16); border: 1px solid #EDEFF6; z-index: 100000; overflow: hidden; }
        .pacs-notify-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 1px solid #F0F2F8; }
        .pacs-notify-title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 700; color: #1F2433; }
        .pacs-notify-count { font-size: 12px; font-weight: 600; color: #EF4444; }
        .pacs-notify-readall { font-size: 13px; color: #6B7185 !important; text-decoration: none !important; }
        .pacs-notify-readall:hover { color: #4338CA !important; }
        .pacs-notify-list { max-height: 420px; overflow-y: auto; padding: 6px 8px 10px; }
        .pacs-notify-list::-webkit-scrollbar { width: 6px; }
        .pacs-notify-list::-webkit-scrollbar-thumb { background: #D7DBEA; border-radius: 10px; }
        .pacs-notify-group-label { font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #9AA0B4; padding: 10px 10px 4px; }
        .pacs-notify-item { position: relative; display: flex; align-items: flex-start; gap: 12px; padding: 12px 10px; border-radius: 12px; text-decoration: none !important; cursor: pointer; transition: background-color .12s ease; }
        .pacs-notify-item:hover { background: #EEF1FB; }
        .pacs-notify-item._unread { background: #F7F8FE; }
        .pacs-notify-item._unread:hover { background: #E9EDFB; }
        .pacs-notify-ico { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; flex: 0 0 auto; margin-top: 2px; }
        .pacs-notify-ico.error { background: #FEF2F2; color: #EF4444; }
        .pacs-notify-ico.warning { background: #FFFBEB; color: #F59E0B; }
        .pacs-notify-ico.info { background: #EEF2FF; color: #6366F1; }
        .pacs-notify-content { flex: 1 1 auto; min-width: 0; }
        .pacs-notify-item-title { font-size: 13.5px; font-weight: 600; color: #1F2433; line-height: 1.35; }
        .pacs-notify-item-desc { font-size: 12.5px; color: #7B8194; line-height: 1.4; margin-top: 2px; }
        .pacs-notify-aside { display: flex; align-items: center; gap: 6px; flex: 0 0 auto; margin-top: 2px; }
        .pacs-notify-time { font-size: 12px; color: #9AA0B4; white-space: nowrap; }
        .pacs-notify-unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #6366F1; flex: 0 0 auto; }
        .pacs-notify-empty { padding: 28px 10px; text-align: center; color: #9AA0B4; font-size: 14px; }
        .pacs-link { color: #6366F1; text-decoration: none; }
        .pacs-link:hover { color: #4338CA; text-decoration: underline; }
        @media (max-width: 1024px) {
            .pacs-notify-panel {
                position: fixed; top: 64px; left: 8px; right: 8px;
                width: auto; max-width: none;
                display: flex; flex-direction: column;
                max-height: calc(100vh - 80px);
                max-height: calc(100dvh - 80px);
            }
            .pacs-notify-head { flex: 0 0 auto; }
            .pacs-notify-list { flex: 1 1 auto; min-height: 0; max-height: none; }
        }
    </style>
@endonce
<div class="pacs-notify" x-data="pacsNotify({{ $unreadCount }})" @click.outside="open = false">
    <button type="button" class="pacs-notify-bell" @click="open = !open" aria-label="Уведомления">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.7 21a2 2 0 0 1-3.4 0"/>
        </svg>
        <span class="pacs-notify-dot" x-show="unread > 0" x-cloak></span>
    </button>

    <div class="pacs-notify-panel" x-show="open" x-transition x-cloak>
        <div class="pacs-notify-head">
            <div class="pacs-notify-title">
                Уведомления
                <span class="pacs-notify-count" x-show="unread > 0" x-cloak><span x-text="unread"></span> новых</span>
            </div>
            <a href="{{ $readAllUrl }}" class="pacs-notify-readall" x-show="unread > 0" x-cloak
               @click.prevent="markAllRead('{{ $readAllUrl }}')">Прочитать все</a>
        </div>

        <div class="pacs-notify-list">
            @forelse($groups as $group)
                <div class="pacs-notify-group-label">{{ $group['label'] }}</div>
                @foreach($group['items'] as $item)
                    <a href="{{ route('moonshine.notifications.read', $item['id']) }}"
                       class="pacs-notify-item @if($item['is_unread']) _unread @endif"
                       @click.prevent="markRead($el, '{{ route('moonshine.notifications.read', $item['id']) }}')">
                        <span class="pacs-notify-ico {{ $item['type'] }}">
                            @if($item['type'] === 'error')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                            @elseif($item['type'] === 'warning')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/></svg>
                            @else
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                            @endif
                        </span>

                        <div class="pacs-notify-content">
                            <div class="pacs-notify-item-title">{{ $item['title'] }}</div>
                            @if($item['description'] !== '')
                                <div class="pacs-notify-item-desc">{!! $item['description'] !!}</div>
                            @endif
                        </div>

                        <div class="pacs-notify-aside">
                            <span class="pacs-notify-time">{{ $item['time'] }}</span>
                            @if($item['is_unread'])
                                <span class="pacs-notify-unread-dot"></span>
                            @endif
                        </div>
                    </a>
                @endforeach
            @empty
                <div class="pacs-notify-empty">Нет уведомлений</div>
            @endforelse
        </div>
    </div>
</div>

@once
    <script>
        function pacsNotify(count) {
            return {
                open: false,
                unread: count,
                _mark(url) {
                    // Асинхронно помечаем прочитанным, ответ (redirect) игнорируем.
                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        redirect: 'manual',
                    }).catch(() => {});
                },
                markRead(el, url) {
                    if (!el.classList.contains('_unread')) return;
                    this._mark(url);
                    el.classList.remove('_unread');
                    el.querySelector('.pacs-notify-unread-dot')?.remove();
                    this.unread = Math.max(0, this.unread - 1);
                },
                markAllRead(url) {
                    this._mark(url);
                    this.$root.querySelectorAll('.pacs-notify-item._unread').forEach(el => {
                        el.classList.remove('_unread');
                        el.querySelector('.pacs-notify-unread-dot')?.remove();
                    });
                    this.unread = 0;
                },
            };
        }
    </script>
@endonce
