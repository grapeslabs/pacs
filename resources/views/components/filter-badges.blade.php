@php
    /** @var array $fields */  // [ ['column'=>..., 'label'=>...], ... ]
@endphp

<div class="filter-badges"
     x-data="filterBadges(@js($fields))"
     x-show="active.length > 0"
     x-cloak>
    <template x-for="b in active" :key="b.column">
        <span class="filter-badge">
            <span class="filter-badge__text" x-text="b.label + ': ' + b.value"></span>
            <button type="button" class="filter-badge__remove" aria-label="Убрать фильтр" @click="removeFilter(b.column)">
                <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </span>
    </template>

    <button type="button" class="filter-badges__reset" @click="resetAll()">Сбросить всё</button>
</div>

@once
    <script>
        function filterBadges(fields) {
            return {
                fields: fields || [],
                active: [],
                filtersRoot: '#filters-sidebar_content',

                form() { return document.querySelector(this.filtersRoot + ' form'); },

                init() {
                    this.$nextTick(() => this.refresh());
                    // фильтр применяется без перезагрузки — обновляем бейджи по событиям формы
                    setTimeout(() => this.refresh(), 300);

                    document.addEventListener('submit', (e) => {
                        const form = this.form();
                        if (form && e.target === form) {
                            setTimeout(() => this.refresh(), 150);
                        }
                    }, true);

                    document.addEventListener('click', (e) => {
                        if (e.target.closest && e.target.closest(this.filtersRoot + ' .js-async-reset-button')) {
                            setTimeout(() => this.refresh(), 150);
                        }
                    }, true);

                    window.addEventListener('popstate', () => setTimeout(() => this.refresh(), 100));
                },

                refresh() {
                    const form = this.form();
                    const result = [];
                    if (!form) { this.active = result; return; }

                    for (const f of this.fields) {
                        const els = form.querySelectorAll(
                            `[name="filter[${f.column}]"], [name^="filter[${f.column}]["]`
                        );
                        const vals = [];
                        els.forEach((el) => {
                            if (el.type === 'checkbox' || el.type === 'radio') {
                                if (el.checked && el.value && el.value !== '0') { vals.push(el.value); }
                            } else if (el.value !== '' && el.value != null && el.value !== '0') {
                                vals.push(el.value);
                            }
                        });
                        const value = vals.join(', ');
                        if (value !== '') {
                            result.push({ column: f.column, label: f.label, value });
                        }
                    }
                    this.active = result;
                },

                removeFilter(column) {
                    const form = this.form();
                    if (!form) { return; }

                    form.querySelectorAll(
                        `[name="filter[${column}]"], [name^="filter[${column}]["]`
                    ).forEach((el) => {
                        if (el.type === 'checkbox' || el.type === 'radio') { el.checked = false; }
                        el.value = '';
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    // применяем асинхронно
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    setTimeout(() => this.refresh(), 150);
                },

                resetAll() {
                    const reset = document.querySelector(this.filtersRoot + ' .js-async-reset-button');
                    if (reset) {
                        reset.click();
                    } else {
                        document.dispatchEvent(new CustomEvent('moonshine:filter-reset'));
                        const form = this.form();
                        if (form) {
                            form.querySelectorAll('[name^="filter["]').forEach((el) => {
                                if (el.type === 'checkbox' || el.type === 'radio') { el.checked = false; }
                                el.value = '';
                            });
                            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                        }
                    }
                    setTimeout(() => this.refresh(), 150);
                },
            };
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .filter-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: -12px;
            margin-bottom: 20px;
        }
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 28px;
            padding: 0 6px 0 11px;
            border-radius: 8px;
            background: #D7DBEF;
            color: #4E4E61;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .filter-badge__remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            padding: 0;
            border: none;
            background: transparent;
            border-radius: 50%;
            color: rgba(78, 78, 97, .6);
            cursor: pointer;
            transition: background .15s, color .15s;
        }
        .filter-badge__remove:hover {
            background: rgba(78, 78, 97, .14);
            color: #4E4E61;
        }
        .filter-badges__reset {
            display: inline-flex;
            align-items: center;
            height: 28px;
            padding: 0 4px;
            border: none;
            background: transparent;
            color: #7381F4;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: color .15s;
        }
        .filter-badges__reset:hover {
            color: #5B69E8;
            text-decoration: underline;
        }
    </style>
@endonce
