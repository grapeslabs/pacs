@once
    <script>
        window.msFilters = window.msFilters || {
            root: '#filters-sidebar_content',

            form() {
                return document.querySelector(this.root + ' form');
            },

            inputs(form, column) {
                if (! form) {
                    return [];
                }

                return Array.from(form.querySelectorAll(
                    '[name="filter[' + column + ']"],'
                    + '[name="filter[' + column + '][]"],'
                    + '[name^="filter[' + column + ']["]'
                ));
            },

            columns(form) {
                const columns = new Set();

                if (! form) {
                    return [];
                }

                form.querySelectorAll('[name^="filter["]').forEach((el) => {
                    const match = (el.getAttribute('name') || '').match(/^filter\[([^\]]+)\]/);
                    if (match) {
                        columns.add(match[1]);
                    }
                });

                return Array.from(columns);
            },

            selectWrapper(el) {
                return el.closest('.multiselect-wrapper');
            },

            selectData(wrapper) {
                if (! wrapper || ! window.Alpine) {
                    return null;
                }

                try {
                    const data = window.Alpine.$data(wrapper);

                    return data && Array.isArray(data.selectedIds) ? data : null;
                } catch (e) {
                    return null;
                }
            },

            valueOf(form, column) {
                const parts = [];
                const visited = new Set();

                this.inputs(form, column).forEach((el) => {
                    const wrapper = this.selectWrapper(el);

                    if (wrapper) {
                        if (visited.has(wrapper)) {
                            return;
                        }
                        visited.add(wrapper);

                        const data = this.selectData(wrapper);
                        if (data) {
                            (data.selectedOptions || []).forEach((option) => parts.push(option.name));
                        }
                        return;
                    }

                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.checked && el.value && el.value !== '0') {
                            parts.push(el.value);
                        }
                        return;
                    }

                    const value = (el.value ?? '').toString().trim();
                    if (value !== '' && value !== '0') {
                        parts.push(value);
                    }
                });

                return parts.join(', ');
            },

            clear(column) {
                const form = this.form();
                const els = this.inputs(form, column);
                const hasCheckable = els.some((el) => el.type === 'checkbox' || el.type === 'radio');
                const visited = new Set();

                els.forEach((el) => {
                    const wrapper = this.selectWrapper(el);

                    if (wrapper) {
                        if (visited.has(wrapper)) {
                            return;
                        }
                        visited.add(wrapper);

                        const data = this.selectData(wrapper);
                        if (data) {
                            data.selectedIds = [];
                            data.search = '';
                            data.error = null;
                        }
                        return;
                    }

                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = false;
                    } else if (hasCheckable && el.type === 'hidden') {
                        return;
                    } else {
                        el.value = '';
                    }

                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
            },

            clearAll() {
                const form = this.form();

                this.columns(form).forEach((column) => this.clear(column));
            },

            count() {
                const form = this.form();

                if (! form) {
                    return 0;
                }

                return this.columns(form)
                    .filter((column) => this.valueOf(form, column) !== '')
                    .length;
            },

            apply(reset = false) {
                const form = this.form();

                if (! form) {
                    return;
                }

                if (reset) {
                    form.setAttribute('data-reset', '1');
                }

                const submit = () => {
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    this.sync();
                };

                const defer = () => setTimeout(submit, 0);

                if (window.Alpine && typeof window.Alpine.nextTick === 'function') {
                    window.Alpine.nextTick(defer);
                } else {
                    defer();
                }
            },

            sync() {
                if (window.Alpine && window.Alpine.store && window.Alpine.store('filters')) {
                    window.Alpine.store('filters').count = this.count();
                }

                window.dispatchEvent(new CustomEvent('ms-filters-changed'));
            },
        };
    </script>
@endonce
