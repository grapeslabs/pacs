@php
    $locked = $field->getIsLocked();
    $url = $field->getUnlockUrl();
    $originalValue = (bool) $field->toValue();
    $currentValue = $locked ? false : $originalValue;
@endphp

<style>
    .ff-wrapper {
        height: 50px;
        width: 100%;
        margin-bottom: 1rem;
        font-family: inherit;
        background-color: #FFFFFF;
        border-radius: 8px;
        padding: 8px 12px 4px 12px;
        border: 1px solid #D7D0EF;
    }
    .ff-hidden {
        display: none;
    }

    .ff-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: transparent;
    }

    .ff-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .ff-right {
        align-items: center;
    }

    .ff-icon {
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #7480ff;
    }
    .ff-icon svg {
        width: 100%;
        height: 100%;
    }
    .ff-label {
        color: #7480ff;
        font-weight: 500;
        font-size: 1rem;
    }

    .ff-lock-container {
        position: relative;
        display: flex;
        align-items: center;
        height: 100%;
    }
    .ff-lock-btn {
        color: #7480ff;
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
        background-color: #e2e4ef;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: background-color 0.3s ease-in-out;
    }
    .ff-switcher.is-active {
        background-color: #7480ff;
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
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transform: translateX(2px);
        transition: transform 0.3s ease-in-out;
    }
    .ff-switcher.is-active .ff-switcher-circle {
        transform: translateX(22px);
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
        }
    }"
    class="ff-wrapper"
>
    <input type="hidden" name="{{ $field->getColumn() }}" value="0">
    <input type="checkbox" name="{{ $field->getColumn() }}" value="1" x-model="value" class="ff-hidden">

    <div class="ff-card">
        <div class="ff-left">
            <div class="ff-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M9.93808 3H5.25058C5.11141 3 4.97499 3.03873 4.85658 3.11185C4.73816 3.18496 4.64243 3.28958 4.58008 3.414L1.58008 9.414H8.33458L9.93808 3Z" fill="url(#paint0_linear_440_4905)"/>
                    <path d="M15.666 9.414L14.0625 3H18.75C18.8892 3 19.0256 3.03873 19.144 3.11185C19.2624 3.18496 19.3582 3.28958 19.4205 3.414L22.4205 9.414H15.666Z" fill="url(#paint1_linear_440_4905)"/>
                    <path d="M9.0225 3.5685C9.06299 3.40618 9.15658 3.26205 9.2884 3.15904C9.42022 3.05603 9.5827 3.00005 9.75 3H14.25C14.4173 3.00005 14.5798 3.05603 14.7116 3.15904C14.8434 3.26205 14.937 3.40618 14.9775 3.5685L16.4775 9.5685C16.4925 9.6285 16.5 9.689 16.5 9.75H7.5C7.50007 9.68881 7.50762 9.62786 7.5225 9.5685L9.0225 3.5685Z" fill="url(#paint2_linear_440_4905)"/>
                    <path d="M8.35228 9H1.78678L1.57978 9.414C1.5149 9.54318 1.48874 9.68836 1.50444 9.83207C1.52014 9.97577 1.57704 10.1119 1.66828 10.224L11.4183 22.224C11.4874 22.3087 11.5741 22.3774 11.6725 22.425C11.7709 22.4727 11.8785 22.4983 11.9878 22.5C12.0972 22.5017 12.2055 22.4795 12.3054 22.435C12.4052 22.3904 12.4941 22.3245 12.5658 22.242L8.35228 9Z" fill="url(#paint3_linear_440_4905)"/>
                    <path d="M11.4346 22.242L15.6481 9H22.2136L22.4206 9.414C22.4854 9.54318 22.5116 9.68836 22.4959 9.83207C22.4802 9.97577 22.4233 10.1119 22.3321 10.224L12.5821 22.224C12.513 22.3087 12.4262 22.3774 12.3278 22.425C12.2294 22.4727 12.1218 22.4983 12.0125 22.5C11.9032 22.5017 11.7948 22.4795 11.695 22.435C11.5952 22.3904 11.5063 22.3245 11.4346 22.242Z" fill="url(#paint4_linear_440_4905)"/>
                    <path d="M7.66567 9L7.52317 9.5685C7.48994 9.70204 7.49409 9.84216 7.53517 9.9735L11.2852 21.9735C11.333 22.1258 11.4282 22.2588 11.5569 22.3533C11.6856 22.4477 11.841 22.4986 12.0007 22.4986C12.1603 22.4986 12.3158 22.4477 12.4444 22.3533C12.5731 22.2588 12.6683 22.1258 12.7162 21.9735L16.4662 9.9735C16.5073 9.84216 16.5114 9.70204 16.4782 9.5685L16.3357 9H7.66567Z" fill="url(#paint5_linear_440_4905)"/>
                    <path d="M5.25028 3C5.11111 3.00001 4.97469 3.03873 4.85628 3.11185C4.73786 3.18496 4.64213 3.28958 4.57978 3.414L1.57978 9.414C1.5149 9.54318 1.48874 9.68836 1.50444 9.83207C1.52014 9.97577 1.57704 10.1119 1.66828 10.224L11.4183 22.224C11.4886 22.3105 11.5773 22.3803 11.678 22.4282C11.7787 22.4761 11.8888 22.501 12.0003 22.501C12.1118 22.501 12.2219 22.4761 12.3225 22.4282C12.4232 22.3803 12.512 22.3105 12.5823 22.224L22.3323 10.224C22.4238 10.112 22.4809 9.976 22.4969 9.83229C22.5129 9.68858 22.487 9.54332 22.4223 9.414L19.4223 3.414C19.3598 3.28935 19.2638 3.18458 19.1451 3.11145C19.0264 3.03832 18.8897 2.99973 18.7503 3H5.25028Z" fill="url(#paint6_linear_440_4905)" fill-opacity="0.7"/>
                    <defs>
                        <linearGradient id="paint0_linear_440_4905" x1="8.74408" y1="0.327" x2="5.27008" y2="8.64" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#9FF0F9"/>
                            <stop offset="1" stop-color="#29C3FF"/>
                        </linearGradient>
                        <linearGradient id="paint1_linear_440_4905" x1="17.0475" y1="3" x2="21.006" y2="12.948" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#0FAFFF"/>
                            <stop offset="1" stop-color="#102784"/>
                        </linearGradient>
                        <linearGradient id="paint2_linear_440_4905" x1="12" y1="3" x2="12" y2="11.4375" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#3BD5FF"/>
                            <stop offset="1" stop-color="#367AF2"/>
                        </linearGradient>
                        <linearGradient id="paint3_linear_440_4905" x1="3.45328" y1="6.3" x2="11.7273" y2="22.26" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#0094F0"/>
                            <stop offset="1" stop-color="#6CE0FF"/>
                        </linearGradient>
                        <linearGradient id="paint4_linear_440_4905" x1="25.1041" y1="2.25" x2="12.7756" y2="20.742" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#1B44B1"/>
                            <stop offset="1" stop-color="#2052CB"/>
                        </linearGradient>
                        <linearGradient id="paint5_linear_440_4905" x1="11.9947" y1="4.275" x2="11.9947" y2="22.5" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#2052CB"/>
                            <stop offset="1" stop-color="#0FAFFF"/>
                        </linearGradient>
                        <linearGradient id="paint6_linear_440_4905" x1="-0.632721" y1="-15.363" x2="16.1463" y2="24.0795" gradientUnits="userSpaceOnUse">
                            <stop offset="0.533" stop-color="#FF6CE8" stop-opacity="0"/>
                            <stop offset="1" stop-color="#FF6CE8"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <span class="ff-label">{{ $field->getFeatureLabel() }}</span>
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
