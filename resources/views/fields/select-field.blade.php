<style>
    .multiselect-wrapper {
        position: relative;
    }

    .ms-selected-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 8px;
    }

    .multiselect-box {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
        min-height: 46px;
        padding: 0 12px;
        border-radius: 12px;
        cursor: text;
        transition: all 0.2s ease-in-out;
        border: 1px solid var(--gray-blue-200);
        background: #fff;
    }

    .multiselect-box.active {
        border-color: #7381F4;
        box-shadow: 0 0 0 0.2rem rgba(115, 129, 244, 0.25);
    }

    .ms-field-icon {
        display: flex;
        align-items: center;
        color: #A1A5B7;
        flex-shrink: 0;
    }
    .ms-field-icon svg { width: 18px; height: 18px; }

    .multiselect-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        background-color: #EAEDF8;
        color: #7F7F9D;
        font-weight: 500;
        font-size: 14px;
    }

    .multiselect-tag-remove {
        color: #7F7F9D;
        cursor: pointer;
        display: flex;
        transition: color 0.2s;
        border: none;
        background: none;
        padding: 0;
    }

    .multiselect-tag-remove:hover { color: #F04138; }

    .ms-single-value {
        font-size: 15px;
        font-weight: 500;
        color: #343B41;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*Так и должно быть, это для того что бы перекрыть стили moonshine-custom*/
    .multiselect-input.multiselect-input {
        flex: 1;
        min-width: 120px;
        height: 30px;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: transparent;
        font-size: 15px;
        color: #343B41;
    }
    .multiselect-input.multiselect-input::placeholder { color: #A1A5B7; }

    .multiselect-arrow {
        display: flex; align-items: center; color: #A1A5B7;
        cursor: pointer; transition: transform 0.2s;
        margin-left: auto;
        flex-shrink: 0;
    }
    .multiselect-arrow.rotated {
        transform: rotate(180deg);
    }

    .multiselect-dropdown {
        position: absolute; top: 100%; left: 0; width: 100%;
        margin-top: 6px;
        padding: 6px;
        background-color: #FFFFFF;
        box-shadow: 0 8px 24px rgba(41, 45, 60, 0.12), 0 0 0 1px #EEF0F6;
        border-radius: 12px;
        z-index: 1000;
        overflow: hidden;
    }

    .multiselect-options-list {
        overflow-y: auto;
        padding-right: 4px;
    }

    .multiselect-options-list::-webkit-scrollbar { width: 5px; }
    .multiselect-options-list::-webkit-scrollbar-track { background: transparent; }
    .multiselect-options-list::-webkit-scrollbar-thumb { background-color: #E4E6F1; border-radius: 4px; }

    .multiselect-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        cursor: pointer;
        border-radius: 8px;
        transition: background-color 0.15s;
    }
    .multiselect-option:hover { background-color: #F1F3FB; }
    .multiselect-option.is-selected { background-color: #EFF1FF; }

    .ms-option-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #7381F4;
        flex-shrink: 0;
    }
    .ms-option-icon svg { width: 20px; height: 20px; }

    .ms-option-body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .ms-option-name {
        font-weight: 500;
        font-size: 14px;
        color: #343B41;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ms-option-sub {
        font-size: 12px;
        color: #A1A5B7;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ms-option-check { display: flex; align-items: center; color: #7381F4; flex-shrink: 0; }
    .ms-option-check svg { width: 16px; height: 16px; }

    .multiselect-create-wrapper {
        margin-top: 6px;
        padding-top: 6px;
    }
    .multiselect-create-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .multiselect-create-btn:hover {
        background-color: #F1F3FB;
    }
    .multiselect-create-text {
        color: #7381F4;
        font-weight: 600;
        font-size: 14px;
    }
    .multiselect-create-icon {
        color: #7381F4;
        display: flex;
        align-items: center; }

    .multiselect-box.select-error {
        border-color: #F04138 !important;
        box-shadow: 0 0 0 1px #F04138 !important;
    }

    .select-error-msg {
        color: #F04138;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }
</style>

<div x-data="{
    name: '{{ $element->getNameAttribute() }}',
    options: {{ json_encode($options) }},
    selectedIds: {{ json_encode($selectedIds) }},
    createUrl: '{{ $createUrl }}',
    creatable: {{ $isCreatable ? 'true' : 'false' }},
    multiple: {{ $isMultiple ? 'true' : 'false' }},
    placeholder: '{{ addslashes($placeholder) }}',
    rules: {{ json_encode($customClientRules) }},
    fieldIcon: {{ json_encode($iconSvg) }},
    optionIcon: {{ json_encode($optionIconSvg) }},

    search: '',
    open: false,
    isLoading: false,
    maxOptionsHeight: 220,
    error: null,
    touched: false,

    init() {
        if (!window.selectFieldsRegistry) {
            window.selectFieldsRegistry = new Set();
        }

        window.selectFieldsRegistry.forEach(entry => {
            if (!entry.el.isConnected) {
                window.selectFieldsRegistry.delete(entry);
            }
        });

        window.selectFieldsRegistry.add({
            el: this.$el,
            validateNow: () => this.validateNow(),
        });

        if (!window.selectFieldsInitialized) {
            window.selectFieldsInitialized = true;
            document.addEventListener('submit', function(event) {
                const form = event.target;
                let firstInvalid = null;

                window.selectFieldsRegistry.forEach(entry => {
                    if (!entry.el.isConnected) {
                        window.selectFieldsRegistry.delete(entry);
                        return;
                    }

                    if (form instanceof HTMLFormElement && entry.el.closest('form') !== form) {
                        return;
                    }

                    if (entry.el.offsetParent === null) {
                        return;
                    }

                    if (!entry.validateNow() && firstInvalid === null) {
                        firstInvalid = entry.el;
                    }
                });

                if (firstInvalid) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, true);
        }

        this.$watch('selectedIds', () => {
            this.validate();
            this.$nextTick(() => {
                this.$el.querySelectorAll('input[type=hidden][name]').forEach(input => {
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });

        const form = this.$el.closest('form');
        if (form) {
            form.addEventListener('reset', () => {
                this.selectedIds = [];
                this.search = '';
                this.touched = false;
                this.error = null;
            });
        }

        document.addEventListener('moonshine:filter-reset', () => {
            this.selectedIds = [];
            this.search = '';
            this.touched = false;
            this.error = null;
        });

        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    const rect = this.$el.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom;
                    this.maxOptionsHeight = Math.max(80, spaceBelow - 4 - 12 - 8);
                });
            } else {
                this.touched = true;
                this.validate();
            }
        });
    },

    validate() {
        if (!this.touched) return;
        this.error = null;
        const isRequired = this.rules.find(r => r.type === 'required');
        if (isRequired && this.selectedIds.length === 0) {
            this.error = isRequired.message;
        }
    },

    iconFor(option) { return (option && option.icon) ? option.icon : this.optionIcon; },
    isSelected(option) {
        return this.selectedIds.includes(String(option.id)) || this.selectedIds.includes(Number(option.id));
    },
    validateNow() {
        this.touched = true;
        this.validate();

        return this.error === null;
    },

    get selectedOptions() { return this.selectedIds.map(id => this.options.find(o => o.id == id)).filter(Boolean); },
    get filteredOptions() {
        let available = this.options;
        if (this.multiple) {
            available = available.filter(o => !this.isSelected(o));
        }
        if (this.search === '') return available;
        const lowerSearch = this.search.toLowerCase();
        return available.filter(o => o.name.toLowerCase().includes(lowerSearch)
            || (o.subtext && String(o.subtext).toLowerCase().includes(lowerSearch)));
    },
    get selectedSingleLabel() {
        if (this.multiple || this.selectedIds.length === 0) return '';
        return this.selectedOptions[0]?.name ?? '';
    },
    get showCreate() {
        if (!this.creatable || this.search.trim() === '') return false;
        return !this.options.some(o => o.name.toLowerCase() === this.search.trim().toLowerCase());
    },
    selectOption(option) {
        if (this.multiple) {
            if (!this.isSelected(option)) { this.selectedIds.push(option.id); }
            this.search = '';
            this.$refs.searchInput.focus();
        } else {
            this.selectedIds = [option.id];
            this.search = '';
            this.open = false;
        }
    },
    removeOption(id) {
        this.selectedIds = this.selectedIds.filter(i => i != id);
        this.search = '';
        if (!this.multiple) {
            this.$nextTick(() => this.$refs.searchInput?.focus());
        }
    },
    async createTag() {
        if (!this.creatable || !this.showCreate || this.isLoading) return;
        this.isLoading = true;
        const tagName = this.search.trim();
        try {
            const token = document.querySelector('meta[name=\'csrf-token\']').getAttribute('content');
            if (!this.createUrl) throw new Error('Create URL is missing');
            const response = await fetch(this.createUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ name: tagName }) });
            if (response.ok) { const newTag = await response.json(); this.options.push(newTag); this.selectOption(newTag); }
        } catch (error) { console.error('Error creating:', error); }
        finally { this.isLoading = false; }
    }
}"
     x-cloak
     class="multiselect-wrapper"
     @click.outside="open = false"
>
    <template x-if="multiple && selectedOptions.length > 0">
        <div class="ms-selected-chips">
            <template x-for="option in selectedOptions" :key="option.id">
                <span class="multiselect-tag">
                    <span x-text="option.name"></span>
                    <button type="button" @click.stop="removeOption(option.id)" class="multiselect-tag-remove">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </span>
            </template>
        </div>
    </template>

    <div @click="open = true; $nextTick(() => $refs.searchInput?.focus())" class="multiselect-box" :class="{ 'active': open, 'select-error': error !== null }">
        <span class="ms-field-icon" x-show="fieldIcon" x-html="fieldIcon"></span>
        {{-- Single: выбранное значение внутри поля (когда закрыто и не идёт поиск) --}}
        <template x-if="!multiple && selectedIds.length > 0">
            <span class="ms-single-value" x-show="!open || search === ''" x-text="selectedSingleLabel"></span>
        </template>

        <input
            x-ref="searchInput"
            type="text"
            x-show="multiple || open || selectedIds.length === 0"
            :style="!multiple && selectedIds.length > 0 && (!open || search === '') ? 'width:0;min-width:0;flex:0;' : ''"
            x-model="search"
            :placeholder="selectedIds.length === 0 ? placeholder : (open ? 'Поиск...' : '')"
            @focus="open = true"
            @keydown.enter.prevent="createTag()"
            @keydown.backspace="search === '' && multiple && selectedIds.length > 0 ? removeOption(selectedIds[selectedIds.length - 1]) : null"
            class="multiselect-input"
        >

        <div class="multiselect-arrow" :class="{'rotated': open}" @click.stop="open = !open; if (open) $nextTick(() => $refs.searchInput?.focus())">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>

    <div x-show="open" x-transition class="multiselect-dropdown">
        <div class="multiselect-options-list" :style="'max-height: ' + maxOptionsHeight + 'px'">
            <template x-for="option in filteredOptions" :key="option.id">
                <div @click="selectOption(option)" class="multiselect-option" :class="{ 'is-selected': !multiple && isSelected(option) }">
                    <span class="ms-option-icon" x-show="iconFor(option)" x-html="iconFor(option)"></span>
                    <span class="ms-option-body">
                        <span class="ms-option-name" x-text="option.name"></span>
                        <span class="ms-option-sub" x-show="option.subtext" x-text="option.subtext"></span>
                    </span>
                    <span class="ms-option-check" x-show="!multiple && isSelected(option)">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </span>
                </div>
            </template>
            <div x-show="filteredOptions.length === 0 && !showCreate" style="padding: 16px; text-align: center; color: #777;">Нет данных</div>
        </div>

        <div x-show="showCreate" class="multiselect-create-wrapper">
            <div @click="createTag()" class="multiselect-create-btn">
                <span class="multiselect-create-icon">
                    <svg x-show="!isLoading" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
                    <svg x-show="isLoading" style="animation: spin 1s linear infinite;" width="20" height="20" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                <span class="multiselect-create-text">Добавить "<span x-text="search"></span>"</span>
            </div>
        </div>
    </div>

    <template x-if="multiple">
        <div>
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" :name="name + '[]'" :value="id">
            </template>
            <input {!! $element->getAttributes() !!} type="hidden" :name="selectedIds.length === 0 ? name : null" value="">
        </div>
    </template>

    <template x-if="!multiple">
        <input {!! $element->getAttributes() !!} type="hidden" :name="name" :value="selectedIds.length > 0 ? selectedIds[0] : ''">
    </template>

    <span class="select-error-msg" x-text="error ?? ''" :style="!error ? 'visibility: hidden' : ''"></span>
</div>
