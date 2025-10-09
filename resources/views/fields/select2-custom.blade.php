<div class="select2-wrapper">
    <select
        name="tags_data[]"
        multiple
        class="form-select select2-custom-field"
        data-select2-field="true"
        data-tags="true"
        data-create-url="/api/tags/store"
        data-selected-values="{{ json_encode($value ?? []) }}"
        style="width: 100%; display: block !important;"
    ></select>
</div>

<style>
.select2-container--default .select2-search--inline .select2-search__field {
    height: 22px !important;
    line-height: 24px !important;
    margin: 0 !important;
    padding: 0 !important;
}

.select2-container--default .select2-selection--multiple {
    -webkit-text-size-adjust: 100%;
    tab-size: 4;
    -webkit-tap-highlight-color: transparent;
    -webkit-font-smoothing: antialiased;
    box-sizing: border-box;
    border-style: solid;
    scrollbar-width: thin;
    scrollbar-color: var(--moon-light) transparent;
    font-family: inherit;
    font-feature-settings: inherit;
    font-variation-settings: inherit;
    font-weight: inherit;
    letter-spacing: inherit;
    margin: 0;
    appearance: none;
    padding: .5rem .75rem .80rem;
    min-height: 2.5rem;
    border-radius: .375rem;
    border-width: 1px;
    font-size: .9375rem;
    line-height: 1.5em;
    outline: 2px solid transparent;
    outline-offset: 2px;
    transition: all .2s cubic-bezier(.4,0,.2,1);
    display: block;
    width: 100%;
    border-color: var(--border);
    background-color: var(--background);
    color: var(--text);
}

.select2-container--default .select2-selection--multiple:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px #3b82f6;
    opacity: 0.5;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: white !important;
}

.select2-container--default .select2-dropdown {
    background: white !important;
    border-color: #e2e8f0 !important;
    color: #374151 !important;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.dark .select2-container--default .select2-dropdown,
[data-theme="dark"] .select2-container--default .select2-dropdown,
.dark-mode .select2-container--default .select2-dropdown {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}

.select2-container--default .select2-results > .select2-results__options {
    background: inherit !important;
    color: inherit !important;
}

.select2-container--default .select2-results__option {
    background: inherit !important;
    color: inherit !important;
    padding: 8px 12px;
}

.select2-container--default .select2-results__option:hover {
    background: #f8fafc !important;
    color: inherit !important;
}

.dark .select2-container--default .select2-results__option:hover,
[data-theme="dark"] .select2-container--default .select2-results__option:hover {
    background: #334155 !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background: #f1f5f9 !important;
    color: inherit !important;
}

.dark .select2-container--default .select2-results__option[aria-selected=true],
[data-theme="dark"] .select2-container--default .select2-results__option[aria-selected=true] {
    background: #475569 !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6 !important;
    color: white !important;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    background: inherit !important;
    border-color: inherit !important;
    color: inherit !important;
    border-radius: 4px;
    padding: 6px 8px;
}

.select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: #3b82f6;
    outline: none;
}
</style>

<script>
function initCustomSelect2() {
    const select = document.querySelector('select[data-select2-field="true"]');
    if (select && !select._initialized) {
        select.classList.remove('select2-hidden-accessible', 'choices__input');
        select.removeAttribute('hidden');
        select.removeAttribute('tabindex');
        select.removeAttribute('aria-hidden');
        select.style.display = 'block';

        loadExistingTags(select).then(() => {
            initSelect2Plugin(select);
        });

        select._initialized = true;
    }
}

function initSelect2Plugin(select) {
    select.style.display = 'block';

    loadExistingTags(select).then(() => {
        const select2Instance = $(select).select2({
            tags: true,
            width: '100%',
            placeholder: 'Выберите теги или введите новый',
            allowClear: true,
            dropdownParent: $('body')
        });

        $(select).on('select2:select', function (e) {
            const data = e.params.data;

            if (data.id === data.text) {
                const tempId = data.id;

                fetch('/tags/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ name: data.text })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.tag) {
                        $(select).find(`option[value="${tempId}"]`).remove();

                        const option = new Option(result.tag.name, result.tag.id, true, true);
                        $(select).append(option).trigger('change');

                        const newOption = new Option(result.tag.name, result.tag.id, false, false);
                        select.appendChild(newOption);
                    } else {
                        $(select).find(`option[value="${tempId}"]`).remove();
                        $(select).trigger('change');
                    }
                })
                .catch(error => {
                    console.error('Error creating tag:', error);
                    $(select).find(`option[value="${tempId}"]`).remove();
                    $(select).trigger('change');
                });
            }
        });
    });
}

async function loadExistingTags(select) {
    try {
        const response = await fetch('/tags/list');
        const tags = await response.json();

        if (!select._tagsLoaded) {
            select.innerHTML = '';
            select._tagsLoaded = true;
        }

        tags.forEach(tag => {
            const existingOption = select.querySelector(`option[value="${tag.id}"]`);
            if (!existingOption) {
                const option = new Option(tag.name, tag.id, false, false);
                select.appendChild(option);
            }
        });

        const selectedValuesJson = select.getAttribute('data-selected-values');
        const selectedValues = JSON.parse(selectedValuesJson || '[]');

        if (selectedValues.length > 0) {
            selectedValues.forEach(value => {
                const option = select.querySelector(`option[value="${value}"]`);
                if (option) {
                    option.selected = true;
                }
            });
        }

    } catch (error) {
        console.error('Error loading tags:', error);
    }
}

setTimeout(initCustomSelect2, 300);
setTimeout(initCustomSelect2, 800);
setTimeout(initCustomSelect2, 1500);
</script>
