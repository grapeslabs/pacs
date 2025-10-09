<div class="dadata-organization-wrapper"
     x-data="{
        query: '',
        suggestions: [],
        isOpen: false,
        isLoading: false,

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
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
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
            const orgData = org.data;

            const setFieldValue = (name, value) => {
                const field = document.querySelector(`input[name='${name}']`);
                if (field) {
                    field.value = value;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            setFieldValue('inn', orgData.inn || '');
            setFieldValue('full_name', orgData.name?.full_with_opf || org.value || '');
            setFieldValue('short_name', orgData.name?.short_with_opf || orgData.name?.short || '');
            setFieldValue('address', orgData.address?.unrestricted_value || orgData.address?.value || '');

            if (orgData.management) {
                setFieldValue('contact_data', 'Руководитель: ' + (orgData.management.name || ''));
            } else {
                setFieldValue('contact_data', '');
            }

            this.query = org.value;
            this.isOpen = false;
        }
     }"
     @click.outside="isOpen = false"
>
    <input
        type="text"
        name="dadata_search"
        class="form-input dadata-organization-field"
        placeholder="Введите ИНН или название организации"
        autocomplete="off"
        style="width: 100%;"
        x-model="query"
        @input.debounce.500ms="searchOrganizations()"
        @focus="if(suggestions.length > 0) isOpen = true"
    />

    <div x-show="isLoading" class="dadata-loading" style="display: none; position: absolute; right: 10px; top: 12px; color: #888;">
        <svg style="animation: spin 1s linear infinite;" width="20" height="20" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
    </div>

    <div class="dadata-suggestions" x-show="isOpen" style="display: none;" x-transition>
        <template x-for="(org, index) in suggestions" :key="index">
            <div class="suggestion-item" @click="selectOrganization(org)">
                <span x-text="org.value + ' (ИНН: ' + org.data.inn + ')'"></span>
            </div>
        </template>
        <div x-show="suggestions.length === 0" class="suggestion-item" style="color: #888; cursor: default;">
            Ничего не найдено
        </div>
    </div>
</div>

<style>
    .dadata-organization-wrapper {
        position: relative;
    }

    .dadata-suggestions {
        position: absolute;
        background: #ffffff;
        border: 1px solid var(--gray-blue-200, #ccc);
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        max-height: 250px;
        overflow-y: auto;
        z-index: 9999;
        width: 100%;
        top: calc(100% + 5px);
        left: 0;
    }

    .suggestion-item {
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
        color: #333;
        font-size: 14px;
        transition: background-color 0.15s;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover {
        background-color: #F1F3FB;
    }
</style>
