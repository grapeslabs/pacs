<style>
    .auth-field-container {
        margin-bottom: 1.5rem;
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
    }

    .auth-label {
        display: block;
        font-size: 0.875rem;
        color: #9ca3af;
        margin-bottom: 0.25rem;
    }

    .auth-input-wrapper {
        position: relative;
        width: 100%;
    }

    .auth-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        font-size: 1rem;
        color: #1f2937;
        background-color: #ffffff;
    }

    .auth-input:focus {
        border-color: #828df8;
        box-shadow: 0 0 0 2px rgba(130, 141, 248, 0.2);
    }

    .auth-input.is-invalid {
        border-color: #ef4444 !important;
        padding-right: 2.5rem;
    }

    .auth-error-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.25rem;
        height: 1.25rem;
        pointer-events: none;
    }

    .auth-error-text {
        color: #ef4444;
        font-size: 0.75rem;
        margin-top: 0.5rem;
        display: block;
    }
</style>

@php
    $fieldName = $element->getName();
    $serverError = '';
    $keysToCheck = array_unique([$fieldName, 'email', 'username']);
    $sessionErrors = session()->get('errors') ?: new \Illuminate\Support\MessageBag();

    foreach($keysToCheck as $key) {
        if ($sessionErrors->has($key)) {
            $serverError = $sessionErrors->first($key);
            break;
        }
    }
    $inputType = in_array($fieldName, ['email', 'username']) ? 'email' : 'text';
@endphp

<div
    class="auth-field-container"
    x-data="{
        inputValue: '{{ old($fieldName, (string) $element->getValue()) }}',
        serverError: '{{ $serverError }}',
        clientError: '',
        validate(isOnBlur = false) {
            this.serverError = '';

            if (isOnBlur && this.inputValue.trim() === '') {
                this.clientError = 'Заполните поле';
                return;
            }

            if (this.inputValue.trim() !== '') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.inputValue)) {
                    this.clientError = 'Допустимый формат: ivan@gmail.com';
                } else {
                    this.clientError = '';
                }
            } else {
                this.clientError = '';
            }
        }
    }"
>
    <label for="{{ $fieldName }}" class="auth-label">
        {{ $element->getLabel() }}
    </label>

    <div class="auth-input-wrapper">
        <input
            x-model="inputValue"
            @input.debounce.300ms="validate()"
            @blur="validate(true)"
            :class="{'is-invalid': serverError || clientError}"
            {{ $element->getAttributes()->merge([
                'id' => $fieldName,
                'name' => $fieldName,
                'type' => $inputType,
            ])->class(['auth-input']) }}
        />
        <div x-show="serverError || clientError" style="display: none;">
            <svg class="auth-error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444">
                <circle cx="12" cy="12" r="10"/>
                <path fill="#ffffff" d="M11 7h2v6h-2zm0 8h2v2h-2z"/>
            </svg>
        </div>
    </div>

    <span
        x-show="serverError || clientError"
        x-text="serverError || clientError"
        class="auth-error-text"
        style="display: none;"
    ></span>
</div>
