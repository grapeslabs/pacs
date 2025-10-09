<style>
    .multiselect-wrapper {
        position: relative;
    }

    .multiselect-box {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        width: 100%;
        min-height: 46px;
        padding: 6px 12px;
        border-radius: 12px;
        cursor: text;
        transition: all 0.2s ease-in-out;
        border: 1px solid var(--gray-blue-200);
    }

    .multiselect-box.active {
        box-shadow: 0 0 0 0.2rem rgba(55, 107, 244, 0.5);
    }

    .multiselect-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        background-color: #D7DBEF;
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

    .multiselect-tag-remove:hover { color: #F1416C; }


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
        color: #7381F4;
    }

    .multiselect-arrow {
        display: flex; align-items: center; color: #A1A5B7;
        pointer-events: none; transition: transform 0.2s;
    }
    .multiselect-arrow.rotated {
        transform: rotate(180deg);
    }

    .multiselect-dropdown {
        position: absolute; top: 100%; left: 0; width: 100%;
        margin-top: 8px;
        padding: 6px;
        background-color: #FFFFFF;
        box-shadow: 0 0 0 0.2rem #F3F3F3;
        border-radius: 12px;
        z-index: 1000;
        overflow: hidden;
    }

    .multiselect-options-list {
        max-height: 220px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .multiselect-options-list::-webkit-scrollbar { width: 5px; }
    .multiselect-options-list::-webkit-scrollbar-track { background: transparent; }
    .multiselect-options-list::-webkit-scrollbar-thumb { background-color: #FFFFFF; border-radius: 4px; }

    .multiselect-option {
        padding: 12px 14px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        color: #343B41;
        border-radius: 8px;
        transition: background-color 0.15s;
    }
    .multiselect-option:hover { background-color: #F1F3FB; }

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
        color: #8A93FF;
        font-weight: 600;
        font-size: 14px;
    }
    .multiselect-create-icon {
        color: #8A93FF;
        display: flex;
        align-items: center; }
</style>

<div x-data="{
    name: '{{ $element->getNameAttribute() }}',
    options: {{ json_encode($options) }},
    selectedIds: {{ json_encode($selectedIds) }},
    createUrl: '{{ $createUrl }}',
    creatable: {{ $isCreatable ? 'true' : 'false' }},
    multiple: {{ $isMultiple ? 'true' : 'false' }},

    search: '',
    open: false,
    isLoading: false,

    init() {
        const form = this.$el.closest('form');
        if (form) {
            form.addEventListener('reset', () => {
                this.selectedIds = [];
                this.search = '';
            });
        }

        document.addEventListener('moonshine:filter-reset', () => {
            this.selectedIds = [];
            this.search = '';
        });
    },

    get selectedOptions() { return this.selectedIds.map(id => this.options.find(o => o.id == id)).filter(Boolean); },
    get filteredOptions() {
        let available = this.options;
        if (this.multiple) {
            available = available.filter(o => !this.selectedIds.includes(String(o.id)) && !this.selectedIds.includes(Number(o.id)));
        }
        if (this.search === '') return available;
        const lowerSearch = this.search.toLowerCase();
        return available.filter(o => o.name.toLowerCase().includes(lowerSearch));
    },
    get showCreate() {
        if (!this.creatable || this.search.trim() === '') return false;
        return !this.options.some(o => o.name.toLowerCase() === this.search.trim().toLowerCase());
    },
    selectOption(option) {
        if (this.multiple) {
            if (!this.selectedIds.includes(String(option.id)) && !this.selectedIds.includes(Number(option.id))) { this.selectedIds.push(option.id); }
            this.search = '';
            this.$refs.searchInput.focus();
        } else {
            this.selectedIds = [option.id];
            this.search = option.name;
            this.open = false;
        }
    },
    removeOption(id) { this.selectedIds = this.selectedIds.filter(i => i != id); },
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
    <div @click="open = true" class="multiselect-box" :class="{ 'active': open }">
        <template x-for="option in selectedOptions" :key="option.id">
            <span class="multiselect-tag">
                <span x-text="option.name"></span>
                <button type="button" @click.stop="removeOption(option.id)" class="multiselect-tag-remove">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </span>
        </template>

        <input
            x-ref="searchInput"
            type="text"
            x-model="search"
            @focus="open = true"
            @keydown.enter.prevent="createTag()"
            @keydown.backspace="search === '' && selectedIds.length > 0 ? removeOption(selectedIds[selectedIds.length - 1]) : null"
            class="multiselect-input"
        >

        <div class="multiselect-arrow" :class="{'rotated': open}">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>

    <div x-show="open" x-transition class="multiselect-dropdown">
        <div class="multiselect-options-list">
            <template x-for="option in filteredOptions" :key="option.id">
                <div @click="selectOption(option)" class="multiselect-option" x-text="option.name"></div>
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
            <input type="hidden" :name="selectedIds.length === 0 ? name : null" value="">
        </div>
    </template>

    <template x-if="!multiple">
        <input type="hidden" :name="name" :value="selectedIds.length > 0 ? selectedIds[0] : ''">
    </template>
</div>
