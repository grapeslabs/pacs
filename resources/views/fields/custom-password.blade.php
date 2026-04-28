<style>
    .cpf-wrapper {
        position: relative;
        width: 100%;
        display: block;
    }

    .cpf-input-container {
        position: relative;
        width: 100%;
    }

    .cpf-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 0.5rem 4rem 0.5rem 0.75rem;
        background-color: transparent;
        transition: all 0.2s;
        outline: none;
    }

    .cpf-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 1px #3b82f6;
    }

    .cpf-input.cpf-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 1px #ef4444 !important;
    }

    .cpf-error-msg {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }

    .cpf-icon-wrapper {
        position: absolute;
        right: 2.5rem;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .cpf-icon-error {
        color: #ef4444;
        font-weight: bold;
        font-size: 1.1rem;
        user-select: none;
        pointer-events: none;
    }
</style>

<div x-data="{
    rules: {{ json_encode($element->getCustomClientRules()) }},
    value: '{{ addslashes((string) $element->getValue()) }}',
    error: null,
    init() {
        if (!window.customPasswordFieldsInitialized) {
            window.customPasswordFieldsInitialized = true;
            document.addEventListener('submit', function(event) {
                const errorField = document.querySelector('.cpf-error');
                if (errorField) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    errorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    errorField.focus();
                }
            }, true);
        }

        if (this.value !== null && this.value !== '') {
            this.validate();
        }
        this.$watch('value', () => {
            this.validate();
        });
    },
    validate() {
        this.error = null;
        let isRequired = this.rules.find(r => r.type === 'required');

        if (this.value === '' || this.value === null) {
            if (isRequired) {
                this.error = isRequired.message;
            }
            return;
        }

        for (let rule of this.rules) {
            if (rule.type === 'required') continue;

            if (rule.type === 'min' && String(this.value).length < rule.value) { this.error = rule.message; return; }
            if (rule.type === 'max' && String(this.value).length > rule.value) { this.error = rule.message; return; }

            if (rule.type === 'hasUpper' && !/[A-ZА-ЯЁ]/u.test(this.value)) { this.error = rule.message; return; }
            if (rule.type === 'hasLower' && !/[a-zа-яё]/u.test(this.value)) { this.error = rule.message; return; }
            if (rule.type === 'hasDigit' && !/[0-9]/.test(this.value)) { this.error = rule.message; return; }
            if (rule.type === 'hasSpecial' && !/[!@#$%^&*()_+\-=\[\]{};':\x22\\|,.<>\/?]/.test(this.value)) { this.error = rule.message; return; }

            if (rule.type === 'confirm') {
                let targetInput = document.querySelector(`input[name='${rule.field}']`);
                if (targetInput && this.value !== targetInput.value) {
                    this.error = rule.message;
                    return;
                }
            }
        }
    }
}" @input.window="if ($event.target.name && rules.some(r => r.field === $event.target.name)) validate()"
     class="cpf-wrapper" x-cloak>
    <div class="cpf-input-container">
        <input {!! $element->getAttributes()->merge(['class' => 'cpf-input form-input', 'type' => 'password']) !!}
               name="{{ $element->getName() }}"
               x-model="value"
               @blur="validate()"
               :class="{'cpf-error': error !== null}"
        />
        <div class="cpf-icon-wrapper">
            <template x-if="error !== null">
                <div class="cpf-icon-error">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 18C12.34 18 12.6252 17.8848 12.8556 17.6544C13.086 17.424 13.2008 17.1392 13.2 16.8C13.1992 16.4608 13.084 16.176 12.8544 15.9456C12.6248 15.7152 12.34 15.6 12 15.6C11.66 15.6 11.3752 15.7152 11.1456 15.9456C10.916 16.176 10.8008 16.4608 10.8 16.8C10.7992 17.1392 10.9144 17.4244 11.1456 17.6556C11.3768 17.8868 11.6616 18.0016 12 18ZM12 13.2C12.34 13.2 12.6252 13.0848 12.8556 12.8544C13.086 12.624 13.2008 12.3392 13.2 12V7.2C13.2 6.86 13.0848 6.5752 12.8544 6.3456C12.624 6.116 12.3392 6.0008 12 6C11.6608 5.9992 11.376 6.1144 11.1456 6.3456C10.9152 6.5768 10.8 6.8616 10.8 7.2V12C10.8 12.34 10.9152 12.6252 11.1456 12.8556C11.376 13.086 11.6608 13.2008 12 13.2ZM12 24C10.34 24 8.78 23.6848 7.32 23.0544C5.86 22.424 4.59 21.5692 3.51 20.49C2.43 19.4108 1.5752 18.1408 0.945602 16.68C0.316002 15.2192 0.000801519 13.6592 1.51899e-06 12C-0.000798481 10.3408 0.314402 8.7808 0.945602 7.32C1.5768 5.8592 2.4316 4.5892 3.51 3.51C4.5884 2.4308 5.8584 1.576 7.32 0.9456C8.7816 0.3152 10.3416 0 12 0C13.6584 0 15.2184 0.3152 16.68 0.9456C18.1416 1.576 19.4116 2.4308 20.49 3.51C21.5684 4.5892 22.4236 5.8592 23.0556 7.32C23.6876 8.7808 24.0024 10.3408 24 12C23.9976 13.6592 23.6824 15.2192 23.0544 16.68C22.4264 18.1408 21.5716 19.4108 20.49 20.49C19.4084 21.5692 18.1384 22.4244 16.68 23.0556C15.2216 23.6868 13.6616 24.0016 12 24Z"
                            fill="#F04138"/>
                    </svg>
                </div>
            </template>
        </div>
    </div>
    <template x-if="error !== null">
        <span class="cpf-error-msg" x-text="error"></span>
    </template>
</div>
