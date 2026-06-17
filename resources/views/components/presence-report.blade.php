@props([
    'formHtml' => '',
    'generateUrl' => '',
    'statusUrlTpl' => '',
    'exportUrlTpl' => '',
    'exportCsvUrlTpl' => '',
    'fragment' => null,
    'result' => null,
])

@php
    $renderEmpty = function () {
        return '<div class="custom-table-card"><div class="custom-empty-state">'
            . '<div class="custom-empty-state__illustration">'
            . '<img src="' . asset('images/empty-state.png') . '" width="260" height="185" alt="">'
            . '</div><p class="custom-empty-state__text">Нет данных для отображения</p>'
            . '</div></div>';
    };
@endphp

@if($fragment === 'table')
    @php
        $periods = $result['periods'] ?? [];
        $isWeek = ($result['type'] ?? 'day') === 'week';
        $fmt = function (int $seconds): string {
            return sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
        };
    @endphp

    @if(empty($result['persons']))
        {!! $renderEmpty() !!}
    @else
        <div class="custom-table-card">
            <div class="table-responsive table-sticky">
                <table class="table-list">
                    <thead>
                        <tr>
                            <th rowspan="2" style="vertical-align:middle; text-align:center;">Фото</th>
                            <th rowspan="2" style="vertical-align:middle;">Фамилия</th>
                            @foreach($periods as $p)
                                <th colspan="2" style="text-align:center;">
                                    {{ $p['label'] }}
                                    @if($isWeek && !empty($p['sublabel']))
                                        <div style="font-weight:400; font-size:.8em; opacity:.7;">{{ $p['sublabel'] }}</div>
                                    @endif
                                </th>
                            @endforeach
                            <th colspan="2" style="text-align:center;">Всего время</th>
                        </tr>
                        <tr>
                            @foreach($periods as $p)
                                <th style="text-align:center;">Время</th>
                                <th style="text-align:center;">кол-во</th>
                            @endforeach
                            <th style="text-align:center;">Время</th>
                            <th style="text-align:center;">сессий</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($result['persons'] as $person)
                            <tr>
                                <td style="text-align:center;">
                                    @if(!empty($person['photo']))
                                        <div x-data="{ open: false }" style="display:inline-block;">
                                            <img src="{{ $person['photo'] }}" alt=""
                                                 @click="open = true"
                                                 title="Нажмите для увеличения"
                                                 loading="lazy"
                                                 style="width:36px; height:36px; border-radius:50%; object-fit:cover; cursor:pointer; display:inline-block;">
                                            <template x-teleport="body">
                                                <div x-show="open" x-cloak x-transition.opacity
                                                     @click.self="open = false" @keydown.escape.window="open = false"
                                                     style="position:fixed; top:0; left:0; right:0; bottom:0; width:100vw; height:100vh; background:rgba(0,0,0,0.8); z-index:9999;">
                                                    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);" @click.stop>
                                                        <img src="{{ $person['photo'] }}"
                                                             style="max-width:90vw; max-height:90vh; width:auto; height:auto; object-fit:contain; border-radius:8px; display:block;">
                                                        <button @click="open = false"
                                                                style="position:absolute; top:-12px; right:-12px; background:white; border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:22px; line-height:1; z-index:10000; box-shadow:0 1px 4px rgba(0,0,0,.3);">×</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    @else
                                        <span style="display:inline-block; width:36px; height:36px; border-radius:50%; background:#E3E7F5;"></span>
                                    @endif
                                </td>
                                <td>{{ $person['name'] }}</td>
                                @foreach($periods as $p)
                                    @php
                                        $cell = $person['cells'][$p['key']] ?? null;
                                        $broken = $cell['broken'] ?? [];
                                        $brokenTitle = collect($broken)
                                            ->map(fn ($t) => 'Вход ' . $t . ' → выход не зафиксирован')
                                            ->prepend('Оборванные сессии:')
                                            ->implode("\n");
                                    @endphp
                                    <td style="text-align:center;">
                                        {{ $cell && $cell['count'] > 0 ? $fmt($cell['time']) : '-' }}
                                        @if(!empty($broken))
                                            <span style="color:#f59e0b; cursor:help;" title="{{ $brokenTitle }}">&#9888;</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">{{ $cell && $cell['count'] > 0 ? $cell['count'] : '-' }}</td>
                                @endforeach
                                <td style="text-align:center; font-weight:600;">{{ $fmt($person['total_time']) }}</td>
                                <td style="text-align:center; font-weight:600;">{{ $person['total_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @php return; @endphp
@endif

@once
    <style>
        .custom-table-card {
            border: 1px solid #D7D0EF; border-radius: 16px; overflow: hidden; background: #fff;
            display: flex; flex-direction: column;
            height: calc(100vh - 265px);
        }
        .custom-table-card .table-responsive {
            flex: 1 1 auto; min-height: 0;
            border: none !important; border-radius: 0 !important;
            height: auto !important; overflow-x: auto !important; overflow-y: auto !important; max-height: none !important;
        }
        .custom-table-card .table-responsive.table-sticky {
            overflow: auto !important; max-height: none !important;
            border-bottom: 1px solid #D7D0EF !important;
        }
        .custom-table-card .table-responsive.table-sticky .table-list thead th {
            position: sticky; top: 0; z-index: 2; background: #E3E7F5;
        }
        .custom-table-card .table-list {
            border-collapse: collapse !important; border-spacing: 0 !important;
            margin: 0 !important; font-size: 14px !important; width: 100%;
        }
        .custom-table-card .table-list thead tr { background: transparent !important; box-shadow: none !important; }
        .custom-table-card .table-list thead th {
            border: none !important; border-bottom: 2px solid #D7D0EF !important; background: #E3E7F5 !important;
            padding: 6px 10px !important;
        }
        .custom-table-card .table-list tbody tr { background: #fff !important; transition: background-color .15s !important; }
        .custom-table-card .table-list tbody td {
            border: none !important; border-bottom: 1px solid #EAEDFB !important;
            padding: 4px 10px !important;
        }
        .custom-table-card .table-list tbody tr:hover { background-color: #F5F5F8 !important; }
        .custom-empty-state {
            flex: 1 1 auto;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 64px 24px; gap: 16px; background: #fff;
        }
        .custom-empty-state__illustration {
            display: flex; align-items: center; justify-content: center;
            color: #BEC4E5; margin-bottom: 16px;
        }
        .custom-empty-state__text { font-size: 14px; color: #7F7F9D; text-align: center; margin: 0; }
        .split-export-wrapper { display: none; }
        .presence-spinner {
            width: 56px; height: 56px;
            border: 4px solid #E3E7F5; border-top-color: #6972F0;
            border-radius: 50%; animation: presence-spin 1s linear infinite;
        }
        @keyframes presence-spin { to { transform: rotate(360deg); } }
    </style>
@endonce

<div x-data="presenceReport({
        generateUrl: '{{ $generateUrl }}',
        statusUrlTpl: '{{ $statusUrlTpl }}',
        exportUrlTpl: '{{ $exportUrlTpl }}',
        exportCsvUrlTpl: '{{ $exportCsvUrlTpl }}',
     })"
     @presence-open-form.window="openForm = true">
    <template x-if="state === 'idle'">
        <div>{!! $renderEmpty() !!}</div>
    </template>

    <template x-if="state === 'loading'">
        <div class="custom-table-card">
            <div class="custom-empty-state">
                <div class="custom-empty-state__illustration">
                    <div class="presence-spinner"></div>
                </div>
                <p class="custom-empty-state__text">Идёт формирование отчёта…</p>
            </div>
        </div>
    </template>

    <template x-if="state === 'failed'">
        <div class="custom-table-card">
            <div class="custom-empty-state">
                <p class="custom-empty-state__text" style="color:#dc2626;" x-text="errorMessage"></p>
            </div>
        </div>
    </template>

    <div x-show="state === 'done'" x-cloak x-html="tableHtml"></div>
    <template x-teleport="body">
        <div x-show="openForm" style="display:none; position:fixed; inset:0; z-index:500;">
            <div x-show="openForm" x-transition.opacity.duration.300ms
                 @click="openForm = false"
                 style="position:absolute; inset:0; background:rgba(17,24,39,.75);"></div>

            <div x-show="openForm"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 style="position:absolute; top:0; right:0; bottom:0; width:45vw; min-width:340px; background:#fff; box-shadow:-10px 0 25px -5px rgba(0,0,0,.1); overflow-y:auto;"
                 class="dark:bg-moonshine-dark">

                <div style="padding:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                        <h3 style="font-size:1.125rem; font-weight:500; margin:0;" class="text-gray-900 dark:text-gray-100">
                            Параметры отчёта
                        </h3>
                        <button type="button" @click="openForm = false"
                                style="background:transparent; border:none; cursor:pointer;" class="text-gray-400 hover:text-gray-500">
                            <x-moonshine::icon icon="x-mark" size="6" />
                        </button>
                    </div>

                    <div @submit.prevent="generate($event)">
                        {!! $formHtml !!}
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function presenceReport(cfg) {
        return {
            openForm: false,
            state: 'idle',
            tableHtml: '',
            errorMessage: '',
            reportId: null,
            pollTimer: null,

            async generate(e) {
                const fd = new FormData(e.target);
                this.state = 'loading';
                this.openForm = false;
                this.errorMessage = '';
                this.tableHtml = '';
                try {
                    const res = await fetch(cfg.generateUrl, {
                        method: 'POST',
                        body: fd,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        const first = err.errors ? Object.values(err.errors)[0]?.[0] : null;
                        throw new Error(first || err.message || 'Ошибка параметров отчёта');
                    }
                    const data = await res.json();
                    this.reportId = data.report_id;
                    this.poll();
                } catch (ex) {
                    this.state = 'failed';
                    this.errorMessage = ex.message;
                }
            },

            poll() {
                clearTimeout(this.pollTimer);
                const url = cfg.statusUrlTpl.replace('__ID__', this.reportId);
                fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'done') {
                            this.tableHtml = data.html || '';
                            this.state = 'done';
                            window.dispatchEvent(new CustomEvent('presence-report-ready', {
                                detail: {
                                    excel: cfg.exportUrlTpl.replace('__ID__', this.reportId),
                                    csv: cfg.exportCsvUrlTpl.replace('__ID__', this.reportId),
                                }
                            }));
                        } else if (data.status === 'failed') {
                            this.state = 'failed';
                            this.errorMessage = data.error || 'Не удалось сформировать отчёт';
                        } else {
                            this.pollTimer = setTimeout(() => this.poll(), 2000);
                        }
                    })
                    .catch(() => { this.pollTimer = setTimeout(() => this.poll(), 3000); });
            },
        };
    }

    window.addEventListener('presence-report-ready', (e) => {
        document.querySelectorAll('.split-export-wrapper').forEach((w) => {
            w.style.display = 'inline-flex';
            w.querySelectorAll('a').forEach((a) => {
                a.href = (a.dataset.role === 'csv') ? e.detail.csv : e.detail.excel;
            });
        });
    });
</script>
