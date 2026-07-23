@php
    $locked = $field->getIsLocked();
    $url = $field->getUnlockUrl();
    $originalValue = (bool) $field->toValue();
    $currentValue = $locked ? false : $originalValue;
@endphp

<style>
    .ff-wrapper {
        width: 100%;
        margin-bottom: 0;
        font-family: inherit;
        background: linear-gradient(92deg, #7E92F8 0%, #6972F0 100%);
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 6px 18px -8px rgba(105,114,240,.6);
    }
    .ff-hidden {
        display: none;
    }

    .ff-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: transparent;
        gap: 12px;
    }

    .ff-left {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        min-width: 0;
    }

    .ff-right {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .ff-icon {
        width: 2.25rem;
        height: 2.25rem;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: rgba(255,255,255,.18);
        border-radius: 10px;
    }
    .ff-icon svg {
        width: 1.375rem;
        height: 1.375rem;
    }
    .ff-texts { min-width: 0; }
    .ff-label {
        color: #ffffff;
        font-weight: 600;
        font-size: 1rem;
        line-height: 1.25;
        display: block;
    }
    .ff-desc {
        color: rgba(255,255,255,.85);
        font-size: 0.8125rem;
        margin-top: 2px;
    }

    .ff-lock-container {
        position: relative;
        display: flex;
        align-items: center;
        height: 100%;
    }
    .ff-lock-btn {
        color: #ffffff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem;
        transition: color 0.2s ease;
    }
    .ff-lock-btn:hover {
        color: #5b67e8;
    }
    .ff-lock-btn svg {
        width: 1.5rem;
        height: 1.5rem;
    }

    .ff-tooltip-wrapper {
        position: absolute;
        right: 0;
        top: 100%;
        padding-top: 0.75rem;
        width: 18rem;
        z-index: 50;
    }
    .ff-tooltip-card {
        background-color: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px -15px rgba(0,0,0,0.2);
        padding: 1.25rem;
        border: 1px solid #f3f4f6;
        position: relative;
    }
    .ff-tooltip-arrow {
        position: absolute;
        top: -0.5rem;
        right: 0.5rem;
        width: 1rem;
        height: 1rem;
        background-color: #ffffff;
        transform: rotate(45deg);
        border-left: 1px solid #f3f4f6;
        border-top: 1px solid #f3f4f6;
    }
    .ff-tooltip-text {
        font-size: 0.875rem;
        color: #1f2937;
        margin-top: 0;
        margin-bottom: 1rem;
        position: relative;
        z-index: 10;
        text-align: center;
        line-height: 1.4;
        font-weight: 500;
    }
    .ff-tooltip-link {
        display: block;
        box-sizing: border-box;
        width: 100%;
        text-align: center;
        background-color: #7480ff;
        color: #ffffff;
        padding: 0.625rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.2s ease;
        position: relative;
        z-index: 10;
    }
    .ff-tooltip-link:hover {
        color: #FFFFFF;
        background-color: #5b67e8;
    }

    .ff-switcher {
        position: relative;
        display: inline-flex;
        align-items: center;
        height: 26px;
        width: 46px;
        border-radius: 9999px;
        background-color: rgba(255,255,255,.3);
        border: none;
        padding: 0;
        cursor: pointer;
        transition: background-color 0.3s ease-in-out;
    }
    .ff-switcher.is-active {
        background-color: #ffffff;
    }
    .ff-switcher:focus {
        outline: none;
    }
    .ff-switcher-circle {
        display: inline-block;
        height: 22px;
        width: 22px;
        border-radius: 9999px;
        background-color: #ffffff;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.15);
        transform: translateX(2px);
        transition: transform 0.3s ease-in-out;
    }
    .ff-switcher.is-active .ff-switcher-circle {
        transform: translateX(22px);
        background-color: #6972F0;
    }
</style>

<div
    x-data="{
        value: {{ $currentValue ? 'true' : 'false' }},
        showTooltip: false,
        toggle() {
            if (!{{ $locked ? 'true' : 'false' }}) {
                this.value = !this.value;
            }
        },
        init() {
            this.$watch('value', (val) => {
                this.$dispatch('feature-field-change', { column: '{{ $field->getColumn() }}', value: val });
            });
        }
    }"
    class="ff-wrapper"
>
    <input type="hidden" name="{{ $field->getColumn() }}" value="0">
    <input type="checkbox" name="{{ $field->getColumn() }}" value="1" x-model="value" class="ff-hidden">

    <div class="ff-card">
        <div class="ff-left">
            <div class="ff-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="display: block; color: rgb(255, 255, 255);"><path fill="currentColor" d="M9.107 5.448C9.705 3.698 12.123 3.645 12.832 5.289L12.892 5.449L13.699 7.809C13.8839 8.35023 14.1828 8.84551 14.5754 9.26142C14.9681 9.67734 15.4453 10.0042 15.975 10.22L16.192 10.301L18.552 11.107C20.302 11.705 20.355 14.123 18.712 14.832L18.552 14.892L16.192 15.699C15.6506 15.8838 15.1551 16.1826 14.739 16.5753C14.3229 16.9679 13.9959 17.4452 13.78 17.975L13.699 18.191L12.893 20.552C12.295 22.302 9.877 22.355 9.169 20.712L9.107 20.552L8.301 18.192C8.11618 17.6506 7.81737 17.1551 7.42474 16.739C7.03211 16.3229 6.55479 15.9959 6.025 15.78L5.809 15.699L3.449 14.893C1.698 14.295 1.645 11.877 3.289 11.169L3.449 11.107L5.809 10.301C6.35023 10.1161 6.84551 9.81719 7.26142 9.42457C7.67733 9.03195 8.00421 8.55469 8.22 8.025L8.301 7.809L9.107 5.448ZM11 6.094L10.194 8.454C9.91239 9.2793 9.45427 10.0333 8.85153 10.6635C8.2488 11.2937 7.51594 11.7849 6.704 12.103L6.454 12.194L4.094 13L6.454 13.806C7.2793 14.0876 8.0333 14.5457 8.66349 15.1485C9.29369 15.7512 9.78492 16.4841 10.103 17.296L10.194 17.546L11 19.906L11.806 17.546C12.0876 16.7207 12.5457 15.9667 13.1485 15.3365C13.7512 14.7063 14.4841 14.2151 15.296 13.897L15.546 13.807L17.906 13L15.546 12.194C14.7207 11.9124 13.9667 11.4543 13.3365 10.8515C12.7063 10.2488 12.2151 9.51595 11.897 8.704L11.807 8.454L11 6.094ZM19 2C19.1871 2 19.3704 2.05248 19.5292 2.15147C19.6879 2.25046 19.8157 2.392 19.898 2.56L19.946 2.677L20.296 3.703L21.323 4.053C21.5105 4.1167 21.6748 4.23462 21.7952 4.39182C21.9156 4.54902 21.9866 4.73842 21.9993 4.93602C22.0119 5.13362 21.9656 5.33053 21.8662 5.50179C21.7668 5.67304 21.6188 5.81094 21.441 5.898L21.323 5.946L20.297 6.296L19.947 7.323C19.8832 7.51043 19.7652 7.6747 19.6079 7.79499C19.4507 7.91529 19.2612 7.98619 19.0636 7.99872C18.866 8.01125 18.6692 7.96484 18.498 7.86538C18.3268 7.76591 18.189 7.61787 18.102 7.44L18.054 7.323L17.704 6.297L16.677 5.947C16.4895 5.8833 16.3252 5.76538 16.2048 5.60819C16.0844 5.45099 16.0134 5.26158 16.0007 5.06398C15.9881 4.86638 16.0344 4.66947 16.1338 4.49821C16.2332 4.32696 16.3812 4.18906 16.559 4.102L16.677 4.054L17.703 3.704L18.053 2.677C18.1204 2.47943 18.248 2.30791 18.4178 2.1865C18.5877 2.06509 18.7912 1.99987 19 2Z"></path></svg>
            </div>
            <div class="ff-texts">
                <span class="ff-label">{{ $field->getFeatureLabel() }}</span>
                @if($field->getFeatureDescription())
                    <div class="ff-desc">{{ $field->getFeatureDescription() }}</div>
                @endif
            </div>
        </div>

        <div class="ff-right">
            @if($locked)
                <div class="ff-lock-container" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
                    <div class="ff-lock-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>

                    <div x-show="showTooltip" x-transition.opacity.duration.200ms style="display: none;" class="ff-tooltip-wrapper">
                        <div class="ff-tooltip-card">
                            <div class="ff-tooltip-arrow"></div>
                            <p class="ff-tooltip-text">
                                Чтобы подключить функцию, перейдите в настройки
                            </p>
                            <a href="{{ $url }}" class="ff-tooltip-link">
                                Перейти в настройки
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <button type="button" @click="toggle" :class="{ 'is-active': value }" class="ff-switcher">
                    <span class="ff-switcher-circle"></span>
                </button>
            @endif
        </div>

    </div>
</div>
