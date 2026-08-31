@php
    /** @var array $fieldMap */
@endphp
<div class="org-search"
     x-data="{
        query: '',
        suggestions: [],
        isOpen: false,
        isLoading: false,
        fieldMap: {{ json_encode($fieldMap) }},

        getPath(obj, path) {
            return path.split('.').reduce((o, k) => (o == null ? undefined : o[k]), obj);
        },

        setFieldValue(name, value) {
            const field = document.querySelector(`[name='${name}']`);
            if (!field) return;
            field.value = value ?? '';
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        },

        async searchOrganizations() {
            if (this.query.length < 3) {
                this.isOpen = false;
                this.suggestions = [];
                return;
            }
            this.isLoading = true;
            try {
                const response = await fetch('/organizations/search-dadata?query=' + encodeURIComponent(this.query), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    this.suggestions = await response.json();
                    this.isOpen = this.suggestions.length > 0;
                }
            } catch (error) {
                console.error('Error searching organizations:', error);
            } finally {
                this.isLoading = false;
            }
        },

        selectOrganization(org) {
            for (const [input, path] of Object.entries(this.fieldMap)) {
                if (!path) continue;
                const val = this.getPath(org, path);
                this.setFieldValue(input, (val === undefined || val === null) ? '' : val);
            }
            this.query = org.value;
            this.isOpen = false;
        }
     }"
     @click.outside="isOpen = false"
>
    <div class="org-search__label">Поиск организации</div>
    <div class="org-search__box">
        <span class="org-search__icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="M20 20L17 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </span>
        <input
            type="text"
            name="dadata_search"
            class="org-search__input"
            placeholder="Введите ИНН или название"
            autocomplete="off"
            x-model="query"
            @input.debounce.500ms="searchOrganizations()"
            @focus="if(suggestions.length > 0) isOpen = true"
        />
        <span class="org-search__spin" x-show="isLoading" style="display:none;">
            <svg style="animation: spin 1s linear infinite;" width="18" height="18" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </span>

        <div class="org-search__dropdown" x-show="isOpen" style="display:none;" x-transition>
            <template x-for="(org, index) in suggestions" :key="index">
                <div class="org-search__item" @click="selectOrganization(org)">
                    <span class="org-search__item-name" x-text="org.value"></span>
                    <span class="org-search__item-sub" x-text="'ИНН ' + (org.data.inn || '—') + (org.data.address ? ' · ' + (org.data.address.value || '') : '')"></span>
                </div>
            </template>
            <div x-show="suggestions.length === 0" class="org-search__item" style="cursor:default; color:#9096AE;">
                Ничего не найдено
            </div>
        </div>
    </div>
    <div class="org-search__hint">Автозаполнение реквизитов по ИНН</div>
</div>

@once
<style>
    .org-search {
        position: relative;
        background: #F4F5FC;
        border: 1px solid #E1E4F3;
        border-radius: 14px;
        padding: 16px 18px;
    }
    .org-search__label { font-size: 13px; font-weight: 600; color: #5B6072; margin-bottom: 8px; }
    .org-search__box { position: relative; }
    .org-search__icon {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: #A1A5B7; display: flex; pointer-events: none;
    }
    /* !important — перекрываем глобальные input-стили MoonShine, из-за которых
       сбрасывался левый паддинг и лупа наезжала на текст */
    .org-search .org-search__input {
        width: 100%; box-sizing: border-box;
        height: 46px; padding: 0 40px 0 42px !important;
        border: 1px solid #E1E4F3; border-radius: 12px;
        background: #fff; font-size: 14px; color: #2A2F3B;
        transition: border-color .15s, box-shadow .15s;
    }
    .org-search__input:focus { outline: none; border-color: #8A93FF; box-shadow: 0 0 0 3px rgba(115,129,244,.2); }
    .org-search__input::placeholder { color: #A1A5B7; }
    .org-search__spin { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #8A93FF; }
    .org-search__hint { font-size: 12px; color: #A1A5B7; margin-top: 8px; }

    .org-search__dropdown {
        position: absolute; top: calc(100% + 6px); left: 0; width: 100%;
        background: #fff; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(41,45,60,.12), 0 0 0 1px #EEF0F6;
        max-height: 280px; overflow-y: auto; z-index: 9999; padding: 6px;
    }
    .org-search__item {
        display: flex; flex-direction: column; gap: 2px;
        padding: 10px 12px; border-radius: 8px; cursor: pointer;
        transition: background-color .15s;
    }
    .org-search__item:hover { background: #F1F3FB; }
    .org-search__item-name { font-size: 14px; font-weight: 600; color: #22223A; }
    .org-search__item-sub { font-size: 12px; color: #9096AE; }
</style>
@endonce
