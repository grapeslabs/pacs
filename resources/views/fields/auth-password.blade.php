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
        padding-right: 4.5rem;
    }

    .auth-input:not(.is-invalid) {
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

    .auth-eye-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        cursor: pointer;
        border: none;
        background: transparent;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-eye-btn.has-error {
        right: 2.5rem;
    }

    .auth-eye-btn:not(.has-error) {
        right: 0.75rem;
    }

    .auth-error-text {
        color: #ef4444;
        font-size: 0.75rem;
        margin-top: 0.5rem;
        display: block;
    }

    .auth-input[type="password"]::-ms-reveal,
    .auth-input[type="password"]::-webkit-password-reveal-button {
        display: none !important;
        -webkit-appearance: none !important;
    }
</style>

@php
    $fieldName = $element->getName();
    $serverError = '';
    $sessionErrors = session()->get('errors') ?: new \Illuminate\Support\MessageBag();

    if ($sessionErrors->has($fieldName)) {
        $serverError = $sessionErrors->first($fieldName);
    }
@endphp

<div
    class="auth-field-container"
    x-data="{
        show: false,
        inputValue: '{{ old($fieldName) }}',
        serverError: '{{ $serverError }}',
        clientError: '',
        validate() {
            this.serverError = '';
            if (this.inputValue.trim() === '') {
                this.clientError = 'Заполните поле';
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
            @blur="validate()"
            @input="clientError = ''"
            :type="show ? 'text' : 'password'"
            :class="{'is-invalid': serverError || clientError}"
            {{ $element->getAttributes()->merge([
                'id' => $fieldName,
                'name' => $fieldName,
            ])->class(['auth-input']) }}
        />
        <button type="button" @click="show = !show" class="auth-eye-btn"
                :class="{'has-error': serverError || clientError}">
            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="w-5 h-5" style="width: 1.25rem; height: 1.25rem;">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg x-show="show" style="display: none; width: 1.25rem; height: 1.25rem;"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
            </svg>
        </button>
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
