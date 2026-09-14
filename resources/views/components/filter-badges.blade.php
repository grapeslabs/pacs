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

@include('components.filter-runtime')

@once
    <script>
        function filterBadges(fields) {
            return {
                fields: fields || [],
                active: [],

                init() {
                    this.$nextTick(() => this.refresh());
                    setTimeout(() => this.refresh(), 300);

                    window.addEventListener('ms-filters-changed', () => this.refresh());

                    document.addEventListener('submit', (e) => {
                        if (e.target === window.msFilters.form()) {
                            setTimeout(() => this.refresh(), 150);
                        }
                    }, true);

                    window.addEventListener('popstate', () => setTimeout(() => this.refresh(), 100));
                },

                refresh() {
                    const form = window.msFilters.form();
                    const result = [];

                    if (! form) {
                        this.active = result;
                        return;
                    }

                    for (const f of this.fields) {
                        const value = window.msFilters.valueOf(form, f.column);

                        if (value !== '') {
                            result.push({ column: f.column, label: f.label, value });
                        }
                    }

                    this.active = result;
                },

                removeFilter(column) {
                    window.msFilters.clear(column);
                    window.msFilters.apply();
                    setTimeout(() => this.refresh(), 150);
                },

                resetAll() {
                    window.msFilters.clearAll();
                    window.msFilters.apply(true);
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
