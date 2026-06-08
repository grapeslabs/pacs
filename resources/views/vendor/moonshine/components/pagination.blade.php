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
        <div style="display:flex; align-items:center; gap:10px; margin-top:0;">
            <span>Строк на странице:</span>

            <div style="position:relative; display:flex; align-items:center; background:#e1e5f1; border-radius:8px; height:28px;">
                <select
                    style="appearance:none; -webkit-appearance:none; background:transparent; border:none; outline:none; box-shadow:none; color:#374151; font-weight:500; font-size:13px; padding:0 28px 0 12px; height:100%; cursor:pointer; font-family:inherit;"
                    @change="
                        const url = new URL(window.location.href);
                        url.searchParams.set('per_page', $event.target.value);
                        url.searchParams.delete('page');
                        window.location.href = url.toString();
                    "
                >
                    <option value="10" @selected($per_page == 10)>10</option>
                    <option value="25" @selected($per_page == 25)>25</option>
                    <option value="50" @selected($per_page == 50)>50</option>
                    <option value="100" @selected($per_page == 100)>100</option>
                </select>
                <div style="pointer-events:none; position:absolute; right:8px; display:flex; align-items:center; color:#9da5b6;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            @if(isset($total) && $total > 0)
                <span>из {{ $total }}</span>
            @endif
        </div>
    </div>
@endif
