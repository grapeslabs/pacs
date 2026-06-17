@props([
    'name' => 'default',
    'rows' => [],
    'headRows' => [],
    'footRows' => [],
    'columns' => [],
    'headAttributes',
    'bodyAttributes',
    'footAttributes',
    'asyncUrl',
    'async' => false,
    'simple' => false,
    'notfound' => false,
    'creatable' => false,
    'reindex' => false,
    'reorderable' => false,
    'searchable' => false,
    'sticky' => false,
    'lazy' => false,
    'columnSelection' => false,
    'searchValue' => '',
    'translates' => [],
    'topLeft' => null,
    'topRight' => null,
])

<div class="js-table-builder-container"
    @if($async && $lazy) data-lazy="{{ 'table_updated:' . $name }}" @endif
>
    <div x-data="tableBuilder(
        {{ (int) $creatable }},
        {{ (int) $reorderable }},
        {{ (int) $reindex }},
        {{ (int) $async }},
        '{{ $asyncUrl }}'
    )"
        @defineEvent('table_row_added', $name, 'add(true)')
        @defineEvent('table_reindex', $name, 'resolveReindex')
        @defineEventWhen($async, 'table_updated', $name, 'asyncRequest')
        {{ $attributes }}
    >
        <div
            x-data="{
                selectedCount: 0,
                deleteConfirmOpen: false,
                tableEl() {
                    return document.querySelector(`table[data-name='{{ $name }}']`);
                },
                refresh() {
                    const t = this.tableEl();
                    this.selectedCount = t ? t.querySelectorAll('.js-table-action-row:checked').length : 0;
                },
                clearSelection() {
                    const t = this.tableEl();
                    if (t) {
                        t.querySelectorAll('.js-table-action-row').forEach(cb => { cb.checked = false; });
                        const all = t.querySelector('.js-actions-all-checked');
                        if (all) {
                            all.checked = false;
                            all.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                    this.selectedCount = 0;
                },
                confirmBulkDelete() {
                    this.deleteConfirmOpen = false;
                    const t = this.tableEl();
                    if (!t) { console.warn('[bulk-delete] таблица не найдена'); return; }
                    const ids = [...t.querySelectorAll('.js-table-action-row:checked')]
                        .map(cb => cb.value).filter(Boolean);
                    const btn = t.querySelector(`tfoot [data-button-type='bulk-button']`);
                    if (!ids.length) { console.warn('[bulk-delete] нет выбранных id'); return; }
                    if (!btn) { console.warn('[bulk-delete] bulk-кнопка не найдена (нет права MASS_DELETE?)'); return; }
                    const url = (btn.getAttribute('href') || '').split('?')[0];
                    if (!url || url === '#') { console.warn('[bulk-delete] пустой url', btn); return; }
                    const fd = new FormData();
                    fd.append('_method', 'DELETE');
                    ids.forEach(id => fd.append('ids[]', id));
                    const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-XSRF-TOKEN': xsrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: fd,
                        credentials: 'same-origin',
                    }).then(r => r.json().catch(() => ({}))).then(data => {
                        this.selectedCount = 0;
                        window.dispatchEvent(new CustomEvent(('table_updated:{{ $name }}').toLowerCase(), { bubbles: true }));
                        if (data && data.undoUrl) {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: data.message || 'Записи успешно удалены', undoUrl: data.undoUrl }
                            }));
                        }
                    }).catch((err) => { console.error('[bulk-delete] ошибка запроса', err); });
                },
                pluralize(n) {
                    return n === 1 ? 'запись' : (n >= 2 && n <= 4 ? 'записи' : 'записей');
                },
                hasBulkDelete() {
                    const t = this.tableEl();
                    return !!(t && t.querySelector(`tfoot [data-button-type='bulk-button']`));
                },
            }"
            x-init="
                const t = tableEl();
                if (t) t.addEventListener('change', (e) => {
                    if (e.target.matches && e.target.matches('.js-table-action-row, .js-actions-all-checked')) refresh();
                });
            "
        >
            <div
                x-show="actionsOpen && hasBulkDelete()"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="custom-selection-bar"
            >
                <span>Выбрано: <strong x-text="selectedCount"></strong></span>
                <div class="custom-selection-bar__actions">
                    <button type="button" class="custom-selection-bar__delete-btn" @click="deleteConfirmOpen = true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.021-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Удалить
                    </button>
                    <button type="button" class="custom-selection-bar__close-btn" @click="clearSelection()" aria-label="Снять выделение">&times;</button>
                </div>
            </div>

            <template x-teleport="body">
                <div
                    x-show="deleteConfirmOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="custom-delete-overlay"
                    @click.self="deleteConfirmOpen = false"
                    @keydown.escape.window="deleteConfirmOpen = false"
                >
                    <div class="custom-delete-dialog" @click.stop>
                        <h3>Подтверждение удаления</h3>
                        <p>
                            Вы уверены? Будет удалено
                            <strong x-text="selectedCount"></strong>
                            <span x-text="pluralize(selectedCount)"></span>
                        </p>
                        <div class="custom-delete-dialog__actions">
                            <button type="button" class="custom-delete-dialog__cancel" @click="deleteConfirmOpen = false">Отмена</button>
                            <button type="button" class="custom-delete-dialog__confirm" @click="confirmBulkDelete()">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.021-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <x-moonshine::iterable-wrapper
            :searchable="$async && $searchable"
            :search-placeholder="$translates['search']"
            :search-value="$searchValue"
            :search-url="$asyncUrl"
        >
            <x-slot:topLeft>{!! $topLeft ?? '' !!}</x-slot:topLeft>
            <x-slot:topRight>{!! $topRight ?? '' !!}</x-slot:topRight>

            @if($rows->isNotEmpty())
                <div class="custom-table-card">
                    <x-moonshine::table
                        :simple="$simple"
                        :notfound="$notfound"
                        :attributes="$attributes"
                        :headAttributes="$headAttributes"
                        :bodyAttributes="$bodyAttributes"
                        :footAttributes="$footAttributes"
                        :creatable="$creatable"
                        :sticky="$sticky"
                        :translates="$translates"
                        data-name="{{ $name }}"
                    >
                        @if($headRows->isNotEmpty())
                            <x-slot:thead>
                                @foreach($headRows as $row)
                                    {!! $row !!}
                                @endforeach
                            </x-slot:thead>
                        @endif

                        @if($rows->isNotEmpty())
                            <x-slot:tbody>
                                @foreach($rows as $row)
                                    {!! $row !!}
                                @endforeach
                            </x-slot:tbody>
                        @endif

                        @if($footRows->isNotEmpty())
                            <x-slot:tfoot>
                                @foreach($footRows as $row)
                                    {!! $row !!}
                                @endforeach
                            </x-slot:tfoot>
                        @endif
                    </x-moonshine::table>

                    @if($hasPaginator)
                        <div class="custom-table-paginator">
                            {!! $paginator !!}
                        </div>
                    @endif
                </div>
            @else
                <div class="custom-table-card">
                    <div class="custom-empty-state">
                        <div class="custom-empty-state__illustration">
                            <img src="{{ asset('images/empty-state.png') }}" width="260" height="185" alt="">
                        </div>
                        <p class="custom-empty-state__text">Нет данных для отображения</p>
                    </div>
                </div>
            @endif

            @if($creatable)
                <x-moonshine::layout.divider />
                {!! $createButton !!}
            @endif
        </x-moonshine::iterable-wrapper>
    </div>
</div>

@once
<style>
.custom-selection-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 20px; background: #fff; border-radius: 12px;
    margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08);
    font-size: 14px; color: #1a1a2e;
}
.custom-selection-bar__actions { display: flex; align-items: center; gap: 8px; }
.custom-selection-bar__delete-btn {
    display: inline-flex; align-items: center; gap: 4px; padding: 7px 16px;
    background: #ef4444; color: #fff; border: none; border-radius: 20px;
    font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; font-family: inherit;
}
.custom-selection-bar__delete-btn:hover { background: #dc2626; }
.custom-selection-bar__close-btn {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; background: transparent; border: 1px solid #d1d5db;
    border-radius: 50%; font-size: 18px; line-height: 1; cursor: pointer;
    color: #6b7280; padding: 0; font-family: inherit; transition: background .15s;
}
.custom-selection-bar__close-btn:hover { background: #f3f4f6; }
.custom-delete-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.45);
    display: flex; align-items: center; justify-content: center;
}
.custom-delete-dialog {
    background: #fff; border-radius: 16px; padding: 28px 32px;
    width: 420px; max-width: 90vw; box-shadow: 0 8px 32px rgba(0,0,0,.18);
}
.custom-delete-dialog h3 { margin: 0 0 12px; font-size: 18px; font-weight: 700; color: #1e1e1e; }
.custom-delete-dialog p { margin: 0 0 24px; font-size: 14px; color: #4e4e61; line-height: 1.5; }
.custom-delete-dialog__actions { display: flex; justify-content: flex-end; gap: 10px; }
.custom-delete-dialog__cancel {
    padding: 8px 20px; border: 1px solid #c7c0d8; border-radius: 20px;
    background: #fff; color: #4e4e61; font-size: 14px; font-weight: 600;
    cursor: pointer; font-family: inherit; transition: background .15s;
}
.custom-delete-dialog__cancel:hover { background: #f5f5f8; }
.custom-delete-dialog__confirm {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 8px 20px; border: none; border-radius: 20px;
    background: #ef4444; color: #fff; font-size: 14px; font-weight: 700;
    cursor: pointer; font-family: inherit; transition: background .15s;
}
.custom-delete-dialog__confirm:hover { background: #dc2626; }
.js-table-builder-container tfoot {
    position: fixed; left: -9999px; width: 1px; height: 1px;
    overflow: hidden; pointer-events: none;
}
.custom-table-card {
    border: 1px solid #D7D0EF; border-radius: 16px; overflow: hidden; background: #fff;
    display: flex; flex-direction: column;
    height: calc(100vh - 265px);
}
.custom-table-card .table-responsive {
    flex: 1 1 auto;
    min-height: 0;
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
    margin: 0 !important; font-size: 14px !important;
}
.custom-table-card .table-list thead tr { background: transparent !important; box-shadow: none !important; }
.custom-table-card .table-list thead th {
    border: none !important; border-bottom: 2px solid #D7D0EF !important; background: #E3E7F5 !important;
}
.custom-table-card .table-list tbody tr { background: #fff !important; transition: background-color .15s !important; }
.custom-table-card .table-list tbody tr.frozen { background: #ACACB6 !important; }
.custom-table-card .table-list tbody td {
    border: none !important; border-bottom: 1px solid #EAEDFB !important;
    padding-top: 4px !important; padding-bottom: 4px !important;
}
/* Компактнее аватар/контролы внутри строки, чтобы строки были ниже */
.custom-table-card .table-list tbody td .form-group { margin: 0 !important; }
.custom-table-card .table-list tbody tr:hover { background-color: #F5F5F8 !important; }
.custom-table-card .table-list tbody tr:has(.js-table-action-row:checked) { background-color: rgba(99,102,241,.06) !important; }
.custom-table-card .table-list tbody tr:has(.js-table-action-row:checked):hover { background-color: rgba(99,102,241,.09) !important; }
.custom-table-card .table-list thead th a {
    display: inline-flex !important; align-items: center !important;
    gap: 6px !important; flex-direction: row !important;
}
.custom-table-card .table-list thead th a svg { display: none !important; }
.custom-table-card .table-list thead th a::after {
    content: '';
    flex-shrink: 0;
    width: 16px; height: 16px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M8.97978 5.02021C9.07353 5.11384 9.20061 5.16644 9.33311 5.16644C9.46561 5.16644 9.59269 5.11384 9.68644 5.02021L10.1664 4.54021V11.3335C10.1664 11.4662 10.2191 11.5933 10.3129 11.6871C10.4067 11.7809 10.5338 11.8335 10.6664 11.8335C10.7991 11.8335 10.9262 11.7809 11.02 11.6871C11.1138 11.5933 11.1664 11.4662 11.1664 11.3335V4.54021L11.6464 5.02021C11.6922 5.06934 11.7474 5.10874 11.8088 5.13607C11.8701 5.16339 11.9363 5.17809 12.0034 5.17927C12.0706 5.18046 12.1372 5.16811 12.1995 5.14296C12.2618 5.11781 12.3183 5.08038 12.3658 5.0329C12.4133 4.98542 12.4507 4.92887 12.4759 4.86661C12.501 4.80435 12.5134 4.73766 12.5122 4.67053C12.511 4.60339 12.4963 4.53719 12.469 4.47585C12.4416 4.41452 12.4022 4.35932 12.3531 4.31354L11.0198 2.98021C10.926 2.88658 10.7989 2.83398 10.6664 2.83398C10.5339 2.83398 10.4069 2.88658 10.3131 2.98021L8.97978 4.31354C8.88614 4.40729 8.83355 4.53438 8.83355 4.66688C8.83355 4.79938 8.88614 4.92646 8.97978 5.02021ZM5.83311 11.4602L6.31311 10.9802C6.35888 10.9311 6.41408 10.8917 6.47542 10.8644C6.53675 10.837 6.60296 10.8223 6.6701 10.8212C6.73723 10.82 6.80392 10.8323 6.86618 10.8575C6.92843 10.8826 6.98499 10.92 7.03247 10.9675C7.07995 11.015 7.11738 11.0716 7.14253 11.1338C7.16767 11.1961 7.18002 11.2628 7.17884 11.3299C7.17765 11.397 7.16296 11.4632 7.13563 11.5246C7.1083 11.5859 7.0689 11.6411 7.01978 11.6869L5.68644 13.0202C5.59269 13.1138 5.46561 13.1664 5.33311 13.1664C5.20061 13.1664 5.07353 13.1138 4.97978 13.0202L3.64644 11.6869C3.59732 11.6411 3.55792 11.5859 3.53059 11.5246C3.50326 11.4632 3.48857 11.397 3.48738 11.3299C3.4862 11.2628 3.49855 11.1961 3.52369 11.1338C3.54884 11.0716 3.58627 11.015 3.63375 10.9675C3.68123 10.92 3.73779 10.8826 3.80004 10.8575C3.8623 10.8323 3.92899 10.82 3.99613 10.8212C4.06326 10.8223 4.12947 10.837 4.1908 10.8644C4.25214 10.8917 4.30734 10.9311 4.35311 10.9802L4.83311 11.4602V4.66688C4.83311 4.53427 4.88579 4.40709 4.97956 4.31332C5.07332 4.21956 5.2005 4.16688 5.33311 4.16688C5.46572 4.16688 5.5929 4.21956 5.68666 4.31332C5.78043 4.40709 5.83311 4.53427 5.83311 4.66688V11.4602Z' fill='%237F7F9D'/%3E%3C/svg%3E") no-repeat center;
    background-size: contain;
}
.js-table-action-row, .js-actions-all-checked { cursor: pointer; border-radius: 4px !important; }
.js-table-action-row:checked, .js-actions-all-checked:checked { accent-color: #6366f1; }
.custom-table-paginator {
    background: #E3E7F5; border-top: 2px solid #D7D0EF;
    padding: 8px 16px;
}
.custom-table-paginator .pagination {
    display: flex !important; align-items: center !important; justify-content: space-between !important;
    width: 100% !important; background: transparent !important; border: none !important;
    border-radius: 0 !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important;
}
.custom-table-paginator .pagination-list {
    display: flex !important; align-items: center !important; gap: 2px !important;
    list-style: none !important; margin: 0 !important; padding: 0 !important; background: transparent !important;
}
.custom-table-paginator .pagination-item { list-style: none !important; }
.custom-table-paginator .pagination-results {
    font-size: 13px !important; color: #4e4e61 !important; background: transparent !important; white-space: nowrap !important;
}
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
</style>
@endonce
