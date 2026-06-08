<style>
    .cs-wrapper { position: relative; }

    .cs-box {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
        min-height: 42px;
        padding: 0 12px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border: 1px solid var(--gray-blue-200);
        user-select: none;
    }
    .cs-box.active { box-shadow: 0 0 0 0.2rem rgba(55, 107, 244, 0.5); }
    .cs-box.cs-error { border-color: #ef4444 !important; box-shadow: 0 0 0 1px #ef4444 !important; }

    .cs-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
    }

    .cs-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .cs-placeholder {
        color: #A1A5B7;
        font-size: 15px;
    }

    .cs-arrow {
        display: flex;
        align-items: center;
        color: #A1A5B7;
        pointer-events: none;
        transition: transform 0.2s;
        margin-left: auto;
        flex-shrink: 0;
    }
    .cs-arrow.rotated { transform: rotate(180deg); }

    .cs-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        margin-top: 4px;
        padding: 6px;
        background-color: #fff;
        box-shadow: 0 0 0 0.2rem #F3F3F3;
        border-radius: 12px;
        z-index: 1000;
    }

    .cs-options-list { overflow-y: auto; padding-right: 4px; }
    .cs-options-list::-webkit-scrollbar { width: 5px; }
    .cs-options-list::-webkit-scrollbar-track { background: transparent; }
    .cs-options-list::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 4px; }

    .cs-option {
        padding: 8px 10px;
        cursor: pointer;
        border-radius: 8px;
        transition: background-color 0.15s;
    }
    .cs-option:hover { background-color: #F1F3FB; }

    .cs-clear {
        color: #A1A5B7;
        cursor: pointer;
        display: flex;
        border: none;
        background: none;
        padding: 0;
        margin-left: auto;
        transition: color 0.2s;
    }
    .cs-clear:hover { color: #F1416C; }

    .cs-error-msg {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }
</style>

<div x-data="{
    name: '{{ $element->getNameAttribute() }}',
    options: {{ json_encode($options) }},
    selectedId: '{{ $selectedId }}',
    placeholder: '{{ addslashes($placeholder) }}',
    rules: {{ json_encode($rules) }},

    open: false,
    error: null,
    touched: false,
    maxOptionsHeight: 220,

    init() {
        this.$watch('selectedId', () => this.validate());
        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    const rect = this.$el.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom;
                    this.maxOptionsHeight = Math.max(80, spaceBelow - 16);
                });
            } else {
                this.touched = true;
                this.validate();
            }
        });
        document.addEventListener('moonshine:filter-reset', () => {
            this.selectedId = '';
            this.touched = false;
            this.error = null;
        });
    },

    validate() {
        if (!this.touched) return;
        this.error = null;
        const isRequired = this.rules.find(r => r.type === 'required');
        if (isRequired && !this.selectedId) this.error = isRequired.message;
    },

    get selected() {
        return this.options.find(o => o.id === String(this.selectedId)) ?? null;
    },

    selectOption(option) {
        this.selectedId = option.id;
        this.open = false;
    },

    clear() {
        this.selectedId = '';
    }
}"
     x-cloak
     class="cs-wrapper"
     @click.outside="open = false"
>
    <div @click="open = !open"
         class="cs-box"
         :class="{ 'active': open, 'cs-error': error !== null }"
    >
        <template x-if="selected">
            <span class="cs-badge"
                  :style="'background-color:' + selected.bg + ';color:' + selected.text"
            >
                <span class="cs-dot" :style="'background-color:' + selected.dot"></span>
                <span x-text="selected.name"></span>
            </span>
        </template>

        <template x-if="!selected">
            <span class="cs-placeholder" x-text="placeholder || 'Выберите...'"></span>
        </template>

        <template x-if="selected">
            <button type="button" @click.stop="clear()" class="cs-clear">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </template>

        <div class="cs-arrow" :class="{ 'rotated': open }">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    <div x-show="open" x-transition class="cs-dropdown">
        <div class="cs-options-list" :style="'max-height:' + maxOptionsHeight + 'px'">
            <template x-for="option in options" :key="option.id">
                <div @click="selectOption(option)" class="cs-option">
                    <span class="cs-badge"
                          :style="'background-color:' + option.bg + ';color:' + option.text"
                    >
                        <span class="cs-dot" :style="'background-color:' + option.dot"></span>
                        <span x-text="option.name"></span>
                    </span>
                </div>
            </template>
            <div x-show="options.length === 0"
                 style="padding:16px;text-align:center;color:#777;">
                Нет вариантов
            </div>
        </div>
    </div>

    <input type="hidden" :name="name" :value="selectedId">

    <span class="cs-error-msg"
          x-text="error ?? ''"
          :style="!error ? 'visibility:hidden' : ''">
    </span>
</div>