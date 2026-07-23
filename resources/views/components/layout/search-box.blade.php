@once
    <style>
        .pacs-search { position: relative; width: 100%; max-width: 560px; }
        .pacs-search-field { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 12px; background: #F4F6FC; border: 1px solid #EAEDF6; border-radius: 12px; transition: border-color .15s ease, background-color .15s ease; }
        .pacs-search-field:focus-within { background: #fff; border-color: #C7CEF3; }
        .pacs-search-icon { color: #9AA0B4; flex: 0 0 auto; }
        .pacs-search-field input.pacs-search-input,
        .pacs-search-field input.pacs-search-input:focus,
        .pacs-search-field input.pacs-search-input:hover {
            flex: 1 1 auto; border: 0 !important; outline: none !important; box-shadow: none !important;
            -webkit-appearance: none; appearance: none; background: transparent !important;
            height: 100% !important; min-height: 0 !important; padding: 0 !important; margin: 0 !important;
            border-radius: 0 !important; font-size: 14px; color: #1F2433;
        }
        .pacs-search-input::placeholder { color: #9AA0B4; }
        .pacs-search-clear { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border: none; background: transparent; color: #9AA0B4; cursor: pointer; border-radius: 6px; flex: 0 0 auto; }
        .pacs-search-clear:hover { background: #ECEFF8; color: #2C3142; }
        .pacs-search-dropdown { position: absolute; top: calc(100% + 10px); left: 0; width: 100%; background: #fff; border: 1px solid #EDEFF6; border-radius: 16px; box-shadow: 0 14px 36px rgba(20, 23, 40, 0.16); padding: 8px; max-height: 70vh; overflow-y: auto; z-index: 100000; }
        .pacs-search-group + .pacs-search-group { margin-top: 4px; }
        .pacs-search-group-label { font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #9AA0B4; padding: 10px 10px 4px; }
        .pacs-search-item { display: flex; align-items: center; gap: 12px; padding: 9px 10px; border-radius: 12px; text-decoration: none !important; transition: background-color .12s ease; }
        .pacs-search-item:hover { background: #F4F6FC; }
        .pacs-search-avatar { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; }
        .pacs-search-avatar._ph { background: #ECEBFD; color: #5B53E0; font-size: 13px; font-weight: 700; }
        .pacs-search-avatar._cam { background: #EEF2FF; color: #6366F1; }
        .pacs-search-avatar._org { background: #ECFDF5; color: #10B981; }
        .pacs-search-avatar._car { background: #FFF7ED; color: #F59E0B; }
        .pacs-search-avatar._guest { background: #FDF2F8; color: #DB2777; font-size: 13px; font-weight: 700; }
        .pacs-search-avatar._user { background: #EEF2FF; color: #4F46E5; }
        .pacs-search-text { display: flex; flex-direction: column; min-width: 0; }
        .pacs-search-title { font-size: 14px; font-weight: 600; color: #1F2433; line-height: 1.3; }
        .pacs-search-sub { font-size: 12.5px; color: #8A90A6; line-height: 1.3; margin-top: 1px; }
        .pacs-search-hl { color: #4338CA; font-weight: 700; }
        .pacs-search-empty { padding: 22px 10px; text-align: center; color: #9AA0B4; font-size: 14px; }
        @media (max-width: 1024px) {
            .pacs-search-dropdown {
                position: fixed; top: 76px; left: 8px; right: 8px;
                width: auto; max-width: none; max-height: 60vh;
            }
        }
    </style>
@endonce
<div class="pacs-search"
     x-data="pacsSearch(@js($searchUrl))"
     @click.outside="open = false">
    <div class="pacs-search-field">
        <svg class="pacs-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
        </svg>
        <input type="text" class="pacs-search-input" placeholder="Поиск по системе"
               x-model="q" @input.debounce.250ms="search()" @focus="q.length && (open = true)">
        <button type="button" class="pacs-search-clear" x-show="q.length" @click="reset()" aria-label="Очистить">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <div class="pacs-search-dropdown" x-show="open" x-transition x-cloak>
        <template x-if="loading">
            <div class="pacs-search-empty">Поиск…</div>
        </template>

        <template x-if="!loading && !hasResults()">
            <div class="pacs-search-empty">Ничего не найдено</div>
        </template>

        <template x-if="!loading && results.persons.length">
            <div class="pacs-search-group">
                <div class="pacs-search-group-label">Персоны</div>
                <template x-for="item in results.persons" :key="'p'+item.url">
                    <a :href="item.url" class="pacs-search-item">
                        <template x-if="item.avatar">
                            <img class="pacs-search-avatar" :src="item.avatar" alt="">
                        </template>
                        <template x-if="!item.avatar">
                            <span class="pacs-search-avatar _ph" x-text="initials(item.title)"></span>
                        </template>
                        <span class="pacs-search-text">
                            <span class="pacs-search-title" x-html="hl(item.title)"></span>
                            <span class="pacs-search-sub" x-html="hl(item.subtitle)"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!loading && results.organizations.length">
            <div class="pacs-search-group">
                <div class="pacs-search-group-label">Организации</div>
                <template x-for="item in results.organizations" :key="'o'+item.url">
                    <a :href="item.url" class="pacs-search-item">
                        <span class="pacs-search-avatar _org">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="5" y="3" width="14" height="18" rx="1.5"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-3h4v3"/>
                            </svg>
                        </span>
                        <span class="pacs-search-text">
                            <span class="pacs-search-title" x-html="hl(item.title)"></span>
                            <span class="pacs-search-sub" x-html="hl(item.subtitle)"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!loading && results.cars.length">
            <div class="pacs-search-group">
                <div class="pacs-search-group-label">Автомобили</div>
                <template x-for="item in results.cars" :key="'a'+item.url">
                    <a :href="item.url" class="pacs-search-item">
                        <span class="pacs-search-avatar _car">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 13.5 5 8a2 2 0 0 1 1.9-1.3h10.2A2 2 0 0 1 19 8l2 5.5"/><path d="M3 13.5h18V18h-2v-1H5v1H3z"/><path d="M6.5 16h.01M17.5 16h.01"/>
                            </svg>
                        </span>
                        <span class="pacs-search-text">
                            <span class="pacs-search-title" x-html="hl(item.title)"></span>
                            <span class="pacs-search-sub" x-html="hl(item.subtitle)"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!loading && results.guests.length">
            <div class="pacs-search-group">
                <div class="pacs-search-group-label">Гости</div>
                <template x-for="item in results.guests" :key="'g'+item.url">
                    <a :href="item.url" class="pacs-search-item">
                        <span class="pacs-search-avatar _guest" x-text="initials(item.title)"></span>
                        <span class="pacs-search-text">
                            <span class="pacs-search-title" x-html="hl(item.title)"></span>
                            <span class="pacs-search-sub" x-html="hl(item.subtitle)"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!loading && results.users.length">
            <div class="pacs-search-group">
                <div class="pacs-search-group-label">Пользователи</div>
                <template x-for="item in results.users" :key="'u'+item.url">
                    <a :href="item.url" class="pacs-search-item">
                        <span class="pacs-search-avatar _user">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3 5 6v5c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6z"/>
                            </svg>
                        </span>
                        <span class="pacs-search-text">
                            <span class="pacs-search-title" x-html="hl(item.title)"></span>
                            <span class="pacs-search-sub" x-html="hl(item.subtitle)"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!loading && results.cameras.length">
            <div class="pacs-search-group">
                <div class="pacs-search-group-label">Камеры</div>
                <template x-for="item in results.cameras" :key="'c'+item.url">
                    <a :href="item.url" class="pacs-search-item">
                        <span class="pacs-search-avatar _cam">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="6.5" width="12" height="11" rx="2"/><path d="m15 10.5 6-3.5v10l-6-3.5"/>
                            </svg>
                        </span>
                        <span class="pacs-search-text">
                            <span class="pacs-search-title" x-html="hl(item.title)"></span>
                            <span class="pacs-search-sub" x-html="hl(item.subtitle)"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>
    </div>
</div>

@once
    <script>
        function pacsEmptyResults() {
            return { persons: [], organizations: [], cars: [], guests: [], users: [], cameras: [] };
        }
        function pacsSearch(url) {
            return {
                url: url,
                q: '',
                open: false,
                loading: false,
                results: pacsEmptyResults(),
                _ctrl: null,
                hasResults() {
                    return Object.values(this.results).some(g => Array.isArray(g) && g.length > 0);
                },
                reset() {
                    this.q = '';
                    this.open = false;
                    this.results = pacsEmptyResults();
                },
                async search() {
                    const term = this.q.trim();
                    if (term.length < 1) { this.open = false; this.results = pacsEmptyResults(); return; }
                    this.open = true;
                    this.loading = true;
                    if (this._ctrl) { this._ctrl.abort(); }
                    this._ctrl = new AbortController();
                    try {
                        const res = await fetch(this.url + '?q=' + encodeURIComponent(term), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: this._ctrl.signal,
                        });
                        this.results = await res.json();
                    } catch (e) {
                        if (e.name !== 'AbortError') { this.results = pacsEmptyResults(); }
                    } finally {
                        this.loading = false;
                    }
                },
                _esc(s) {
                    return (s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                },
                hl(text) {
                    const safe = this._esc(text);
                    const term = this.q.trim();
                    if (!term) { return safe; }
                    const esc = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    return safe.replace(new RegExp('(' + esc + ')', 'ig'), '<b class="pacs-search-hl">$1</b>');
                },
                initials(name) {
                    return (name || '').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();
                },
            };
        }
    </script>
@endonce
