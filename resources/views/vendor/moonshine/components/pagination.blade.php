@props([
    'simple' => false,
    'async' => false,
    'has_pages' => false,
    'current_page' => 0,
    'last_page' => 0,
    'per_page' => 0,
    'first_page_url' => '',
    'next_page_url' => '',
    'prev_page_url' => '',
    'last_page_url' => '',
    'to' => 0,
    'from' => 0,
    'total' => 0,
    'links' => [],
    'translates' => [],
])

@if ($has_pages || $total > 0)
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; width:100%; padding:0; background:transparent; border-radius:0; font-size:13px; font-weight:500; color:#9da5b6;">
        <div style="display:flex; align-items:center; white-space:nowrap;">
            @if(isset($total) && $total > 0)
                <span>{{ $from }}–{{ $to }} из {{ $total }}</span>
            @endif
        </div>
        @if ($has_pages)
        <div style="display:flex; align-items:center; gap:24px;">
            <div style="display:flex; align-items:center; gap:8px;">
                @if ($current_page > 1)
                    <a href="{{ $first_page_url }}"
                       @if($async) @click.prevent="asyncRequest" data-page="1" @endif
                       style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#9da5b6; cursor:pointer; text-decoration:none; transition:color .2s;"
                       onmouseover="this.style.color='#7885f7'" onmouseout="this.style.color='#9da5b6'">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                        </svg>
                    </a>
                @else
                    <span style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#d1d5e3; cursor:not-allowed;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                        </svg>
                    </span>
                @endif

                @if ($prev_page_url)
                    <a href="{{ $prev_page_url }}"
                       @if($async) @click.prevent="asyncRequest" data-page="{{ $current_page - 1 }}" @endif
                       style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#9da5b6; cursor:pointer; text-decoration:none; transition:color .2s;"
                       onmouseover="this.style.color='#7885f7'" onmouseout="this.style.color='#9da5b6'">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                @else
                    <span style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#d1d5e3; cursor:not-allowed;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                @endif
            </div>
            @if (!$simple)
                <div style="display:flex; align-items:center; gap:4px;">
                    @php $prevKey = null; @endphp
                    @foreach ($links as $key => $link)
                        @php
                            $cleanLabel = trim(strip_tags(html_entity_decode($link['label'])));
                            $isPrevNext = Str::contains($link['label'], ['laquo', 'raquo', 'Previous', 'Next']);
                            $hasDotsBefore = !$isPrevNext && $prevKey !== null && ($key - $prevKey) > 1;
                            if (!$isPrevNext) $prevKey = $key;
                        @endphp

                        @if ($hasDotsBefore)
                            <span style="display:flex; align-items:center; justify-content:center; min-width:28px; padding:0 6px; height:28px; color:#9da5b6; cursor:default;">
                                ...
                            </span>
                        @endif

                        @if (!$isPrevNext)
                            @if ($link['active'])
                                <span style="display:flex; align-items:center; justify-content:center; min-width:28px; padding:0 6px; height:28px; border-radius:6px; background:#7a88f7; color:#fff; box-shadow:0 1px 3px rgba(0,0,0,.12);">
                                    {{ $cleanLabel }}
                                </span>
                            @else
                                <a href="{{ $link['url'] }}"
                                   @if($async) @click.prevent="asyncRequest" data-page="{{ $cleanLabel }}" @endif
                                   style="display:flex; align-items:center; justify-content:center; min-width:28px; padding:0 6px; height:28px; border-radius:6px; color:#374151; text-decoration:none; transition:background .2s, color .2s; cursor:pointer;"
                                   onmouseover="this.style.background='#e1e5f1'; this.style.color='#7885f7';"
                                   onmouseout="this.style.background=''; this.style.color='#374151';">
                                    {{ $cleanLabel }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Стрелки: Вперёд + В конец --}}
            <div style="display:flex; align-items:center; gap:8px;">
                @if ($next_page_url)
                    <a href="{{ $next_page_url }}"
                       @if($async) @click.prevent="asyncRequest" data-page="{{ $current_page + 1 }}" @endif
                       style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#9da5b6; cursor:pointer; text-decoration:none; transition:color .2s;"
                       onmouseover="this.style.color='#7885f7'" onmouseout="this.style.color='#9da5b6'">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @else
                    <span style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#d1d5e3; cursor:not-allowed;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                @endif

                @if ($current_page < $last_page)
                    <a href="{{ $last_page_url }}"
                       @if($async) @click.prevent="asyncRequest" data-page="{{ $last_page }}" @endif
                       style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#9da5b6; cursor:pointer; text-decoration:none; transition:color .2s;"
                       onmouseover="this.style.color='#7885f7'" onmouseout="this.style.color='#9da5b6'">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5l7.5 7.5-7.5 7.5m6-15l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @else
                    <span style="display:flex; align-items:center; justify-content:center; width:24px; height:24px; color:#d1d5e3; cursor:not-allowed;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5l7.5 7.5-7.5 7.5m6-15l7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                @endif
            </div>

        </div>
        @else
            <div></div>
        @endif

        {{-- Справа: строк на странице --}}
        <style>
            .pp-select { display:flex !important; align-items:center; gap:10px; flex-shrink:0; white-space:nowrap; }
            .pp-select__wrap { position:relative; flex-shrink:0; }
            .pp-select__btn {
                display:flex !important; align-items:center; justify-content:space-between; gap:10px;
                box-sizing:border-box; min-width:72px; height:32px; padding:0 12px;
                background:#fff !important; border:1px solid #E2E6F0 !important; border-radius:8px !important;
                color:#1E1E1E !important; font-weight:600; font-size:13px; line-height:1; cursor:pointer;
                font-family:inherit; transition:border-color .15s, box-shadow .15s;
            }
            .pp-select__btn.is-open { border-color:#7A88F7 !important; box-shadow:0 0 0 3px rgba(122,136,247,.25) !important; }
            .pp-select__chevron { width:14px; height:14px; flex-shrink:0; color:#9da5b6; transition:transform .15s; }
            .pp-select__btn.is-open .pp-select__chevron { transform:rotate(180deg); }
            .pp-select__menu-pos {
                position:absolute; left:50%; right:auto; transform:translateX(-50%); bottom:calc(100% + 6px);
                min-width:100%; z-index:50;
            }
            .pp-select__menu {
                display:flex; flex-direction:column !important; gap:2px;
                background:#fff !important; border:1px solid #E2E6F0 !important; border-radius:12px !important;
                box-shadow:0 8px 24px rgba(30,30,30,.12); padding:6px;
            }
            .pp-select__option {
                display:block !important; width:100% !important; box-sizing:border-box; text-align:center;
                border:none !important; border-radius:8px !important; padding:8px 22px;
                background:transparent; color:#1E1E1E; font-size:13px; font-weight:600; line-height:1.2;
                cursor:pointer; font-family:inherit; transition:background .12s, color .12s;
            }
            .pp-select__option:hover { background:#F4F5FA; }
            .pp-select__option.is-active { background:#EEF0FE !important; color:#7A88F7 !important; }
        </style>

        @php $perPageOptions = [10, 25, 50, 100]; @endphp
        <div
            class="pp-select"
            x-data="{
                open: false,
                current: {{ (int) $per_page }},
                options: @js($perPageOptions),
                select(value) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('per_page', value);
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                }
            }"
            @click.outside="open = false"
            @keydown.escape="open = false"
        >
            <span>Строк на странице:</span>

            <div class="pp-select__wrap">
                <button type="button" class="pp-select__btn" :class="{ 'is-open': open }" @click="open = !open">
                    <span x-text="current"></span>
                    <svg class="pp-select__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div class="pp-select__menu-pos">
                  <div
                    class="pp-select__menu"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    style="transform-origin:bottom center;"
                  >
                    <template x-for="option in options" :key="option">
                        <button
                            type="button"
                            class="pp-select__option"
                            :class="{ 'is-active': option === current }"
                            @click="select(option)"
                            x-text="option"
                        ></button>
                    </template>
                  </div>
                </div>
            </div>
        </div>
    </div>
@endif
