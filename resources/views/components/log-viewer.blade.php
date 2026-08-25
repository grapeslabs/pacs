@php
    $levelColors = [
        'debug' => '#64748b',
        'info' => '#3b82f6',
        'notice' => '#0891b2',
        'warning' => '#f59e0b',
        'error' => '#ef4444',
        'critical' => '#dc2626',
        'alert' => '#a855f7',
        'emergency' => '#b91c1c',
    ];

    $formatSize = static function (int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', ' ') . ' МБ';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', ' ') . ' КБ';
        }

        return $bytes . ' Б';
    };

    // Лента склеена из файлов за разные дни, поэтому дата в строке обязательна
    $formatTime = static function (?string $time): string {
        if (!$time) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($time)->format('d.m H:i:s');
        } catch (\Throwable $e) {
            return $time;
        }
    };

    $pluralFiles = static function (int $count): string {
        $mod100 = $count % 100;
        $mod10 = $count % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'файлов';
        }

        return match ($mod10) {
            1 => 'файл',
            2, 3, 4 => 'файла',
            default => 'файлов',
        };
    };

    $queryFor = static function (array $overrides) use ($state): string {
        $query = array_filter([
            'source' => $state['source'],
            'search' => $state['search'],
            'page' => $state['page'] > 1 ? $state['page'] : null,
        ], static fn ($value) => $value !== null && $value !== '');

        $query['levels'] = $state['selected_levels'];

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($query[$key]);

                continue;
            }

            $query[$key] = $value;
        }

        return $state['page_url'] . '?' . http_build_query($query);
    };
@endphp

<div>
    <div class="box" style="margin-bottom: 1rem;">
        <div class="box-body">
            <form method="GET" action="{{ $state['page_url'] }}"
                  style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">

                <div style="display: flex; flex-direction: column; gap: 0.35rem; min-width: 220px;">
                    <label style="font-size: 0.75rem; color: #94a3b8;">Источник</label>
                    <select name="source" class="form-select" onchange="this.form.submit()">
                        @foreach($sources as $key => $source)
                            <option value="{{ $key }}" @selected($key === $state['source'])>
                                {{ $source['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.35rem; flex: 1; min-width: 240px;">
                    <label style="font-size: 0.75rem; color: #94a3b8;">Поиск по тексту</label>
                    <input type="text" name="search" value="{{ $state['search'] }}" class="form-input"
                           placeholder="Например: SKUD или Actuator">
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Показать</button>
                    <a href="{{ $state['page_url'] }}?source={{ $state['source'] }}" class="btn">Сбросить</a>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; width: 100%;">
                    @foreach($levels as $level => $label)
                        @php $checked = in_array($level, $state['selected_levels'], true); @endphp
                        <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer;
                                      padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem;
                                      border: 1px solid {{ $checked ? $levelColors[$level] : 'rgba(148,163,184,0.35)' }};
                                      color: {{ $checked ? '#fff' : '#94a3b8' }};
                                      background: {{ $checked ? $levelColors[$level] : 'transparent' }};">
                            <input type="checkbox" name="levels[]" value="{{ $level }}" @checked($checked)
                                   onchange="this.form.submit()" style="display: none;">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    @if($state['files'])
        @php $sourceLabel = $sources[$state['source']]['label'] ?? $state['source']; @endphp

        <div class="box" style="margin-bottom: 1rem;">
            <div class="box-body" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600;">{{ $sourceLabel }}</span>
                    <span style="font-size: 0.8rem; color: #94a3b8;">
                        {{ $state['files'] }} {{ $pluralFiles($state['files']) }}
                        · {{ $formatSize($state['size']) }}
                        @if($state['oldest'] && $state['newest'])
                            · с {{ \Carbon\Carbon::createFromTimestamp($state['oldest'])->format('d.m.Y') }}
                            по {{ \Carbon\Carbon::createFromTimestamp($state['newest'])->format('d.m.Y') }}
                        @endif
                    </span>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <a href="{{ $queryFor([]) }}" class="btn">Обновить</a>
                    <a href="{{ $state['download_url'] }}" class="btn">Скачать</a>

                    @if($state['writable'])
                        <form method="POST" action="{{ $state['clear_url'] }}"
                              onsubmit="return confirm('Очистить логи «{{ $sourceLabel }}»? Записи будут удалены безвозвратно.')">
                            @csrf
                            <input type="hidden" name="source" value="{{ $state['source'] }}">
                            <button type="submit" class="btn btn-secondary">Очистить</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if($state['truncated'])
            <div class="box" style="margin-bottom: 1rem; border-left: 3px solid #f59e0b;">
                <div class="box-body" style="font-size: 0.85rem; color: #f59e0b;">
                    Логи слишком объёмные — просматриваются только последние записи.
                    Полное содержимое доступно через «Скачать».
                </div>
            </div>
        @endif
    @endif

    <div class="box">
        <div class="box-body" style="padding: 0;">
            @forelse($entries as $index => $entry)
                @php $color = $levelColors[$entry['level']] ?? '#64748b'; @endphp

                <div x-data="{ open: false }"
                     style="border-bottom: 1px solid rgba(148,163,184,0.18);">
                    <div @click="open = !open"
                         style="display: flex; gap: 1rem; align-items: flex-start; padding: 0.7rem 1rem; cursor: pointer;">
                        <span style="flex-shrink: 0; width: 8.5rem; font-size: 0.78rem; color: #94a3b8; font-family: monospace; padding-top: 0.15rem;">
                            {{ $formatTime($entry['time']) }}
                        </span>

                        <span style="flex-shrink: 0; width: 7.5rem; padding-top: 0.05rem;">
                            <span style="display: inline-block; padding: 0.1rem 0.5rem; border-radius: 9999px;
                                         font-size: 0.7rem; font-weight: 600; color: #fff; background: {{ $color }};">
                                {{ $levels[$entry['level']] ?? $entry['level'] }}
                            </span>
                        </span>

                        @if($entry['channel'])
                            <span style="flex-shrink: 0; width: 6rem; font-size: 0.78rem; color: #94a3b8; padding-top: 0.15rem;">
                                {{ $entry['channel'] }}
                            </span>
                        @endif

                        <span style="flex: 1; font-size: 0.85rem; font-family: monospace; word-break: break-word;
                                     white-space: pre-wrap; line-height: 1.45;"
                              x-bind:style="open ? '' : 'display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;'">{{ $entry['message'] }}</span>

                        @if($entry['context'] || $entry['trace'])
                            <span style="flex-shrink: 0; color: #94a3b8; font-size: 0.75rem; padding-top: 0.15rem;"
                                  x-text="open ? '−' : '+'"></span>
                        @endif
                    </div>

                    <div x-show="open" style="display: none; padding: 0 1rem 1rem 1rem;">
                        @if($entry['context'])
                            <div style="font-size: 0.72rem; color: #94a3b8; margin-bottom: 0.3rem;">Контекст</div>
                            <pre style="margin: 0 0 1rem 0; padding: 0.75rem; border-radius: 0.4rem; overflow-x: auto;
                                        background: rgba(148,163,184,0.12); font-size: 0.78rem; line-height: 1.5;">{{ $entry['context'] }}</pre>
                        @endif

                        @if($entry['trace'])
                            <div style="font-size: 0.72rem; color: #94a3b8; margin-bottom: 0.3rem;">Стек вызовов</div>
                            <pre style="margin: 0; padding: 0.75rem; border-radius: 0.4rem; overflow-x: auto; max-height: 24rem;
                                        background: rgba(148,163,184,0.12); font-size: 0.78rem; line-height: 1.5;">{{ $entry['trace'] }}</pre>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding: 2.5rem 1rem; text-align: center; color: #94a3b8; font-size: 0.9rem;">
                    @if(!$state['files'])
                        В этом источнике пока нет логов.
                    @else
                        Записей по заданным условиям не найдено.
                    @endif
                </div>
            @endforelse
        </div>

        @if($entries || $state['page'] > 1)
            <div class="box-body" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                <span style="font-size: 0.8rem; color: #94a3b8;">Страница {{ $state['page'] }}</span>

                <div style="display: flex; gap: 0.5rem;">
                    @if($state['page'] > 1)
                        <a href="{{ $queryFor(['page' => $state['page'] - 1]) }}" class="btn">Новее</a>
                    @endif

                    @if($state['has_more'])
                        <a href="{{ $queryFor(['page' => $state['page'] + 1]) }}" class="btn">Старее</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
