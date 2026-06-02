@php
    $resolved = $element->getValue();
    $val = is_array($resolved) ? (int) ($resolved['value'] ?? 0) : 0;
    $un = is_array($resolved) ? (int) ($resolved['unit'] ?? 24) : 24;
    $name = $element->getNameAttribute();
@endphp

<style>
    .ad-field-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .ad-main-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ad-capsule {
        display: flex;
        align-items: stretch;
        height: 38px;
        position: relative;
    }

    .ad-half-left {
        display: flex;
        align-items: center;
        background: #f4f5f8;
        border: 1px solid transparent;
        border-radius: 8px 0 0 8px;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .ad-half-left.is-focused {
        background: #f8fafc !important;
        border-color: #818cf8 !important;
        z-index: 2;
    }

    .ad-input {
        width: 50px;
        height: 100%;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        text-align: center;
        font-size: 15px;
        color: #111827;
        padding: 0;
        margin: 0;
        transition: color 0.2s ease;
    }

    .ad-half-left.is-focused .ad-input {
        color: #6366f1 !important;
    }

    .ad-input::-webkit-outer-spin-button,
    .ad-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .ad-input[type=number] {
        -moz-appearance: textfield;
    }

    .ad-half-right {
        display: flex;
        align-items: center;
        background: #f4f5f8;
        border: 1px solid transparent;
        border-radius: 0 8px 8px 0;
        padding: 0 10px 0 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        box-sizing: border-box;
        margin-left: -1px;
    }

    .ad-half-right.is-open {
        background: #f8fafc !important;
        border-color: #818cf8 !important;
        z-index: 3;
    }

    .ad-dropdown-text {
        font-size: 14px;
        color: #111827;
        margin-right: 6px;
        transition: color 0.2s ease;
    }
    .ad-half-right > svg {
        width: 16px;
        height: 16px;
        color: #9ca3af;
        transition: all 0.2s ease;
    }

    .ad-half-right.is-open .ad-dropdown-text,
    .ad-half-right.is-open > svg {
        color: #6366f1 !important;
    }
    .ad-half-right.is-open > svg {
        transform: rotate(180deg);
    }

    .ad-dropdown-menu {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        width: 140px;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #f3f4f6;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        padding: 6px;
        z-index: 50;
    }

    .ad-dropdown-item {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        font-size: 14px;
        color: #374151;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.15s;
        text-align: left;
    }
    .ad-dropdown-item:hover, .ad-dropdown-item.is-selected {
        background: #f3f4f6 !important;
    }
    .ad-dropdown-item svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .ad-hint {
        font-size: 15px;
        color: #9ca3af;
    }
</style>

<div class="ad-field-container">
    <div class="ad-main-row" x-data="{
        displayValue: {{ $val }},
        unit: {{ $un }},
        open: false,
        inputFocused: false,
        units: [
            { val: 1,    label: 'Часы',   unitLabel: 'часов',   icon: 'clock'    },
            { val: 24,   label: 'Дни',    unitLabel: 'дней',    icon: 'calendar' },
            { val: 168,  label: 'Недели', unitLabel: 'недель',  icon: 'calendar' },
            { val: 720,  label: 'Месяцы', unitLabel: 'месяцев', icon: 'calendar' },
            { val: 8760, label: 'Годы',   unitLabel: 'лет',     icon: 'calendar' }
        ],
        selectUnit(u) {
            this.unit = parseInt(u);
            this.open = false;
        },
        getUnitLabel() {
            const found = this.units.find(i => i.val === this.unit);
            return found ? found.unitLabel : 'дней';
        },
        getHintText() {
            let val = parseFloat(this.displayValue);
            if (isNaN(val) || val < 0) val = 0;
            let total = val * parseInt(this.unit);
            if (total === 0) return '≈ 0 часов (архивирование отключено)';
            const cases = [2, 0, 1, 1, 1, 2];
            const titles = ['час', 'часа', 'часов'];
            const title = titles[(total % 100 > 4 && total % 100 < 20) ? 2 : cases[(total % 10 < 5) ? Math.abs(total) % 10 : 5]];
            return '≈ ' + total + ' ' + title;
        }
    }">

        <div class="ad-capsule" @click.outside="open = false">
            <div class="ad-half-left" :class="{'is-focused': inputFocused}">
                <input
                    type="number"
                    name="{{ $name }}[value]"
                    class="ad-input"
                    x-model="displayValue"
                    min="0"
                    step="1"
                    @focus="inputFocused = true; open = false"
                    @blur="inputFocused = false"
                >
            </div>
            <input type="hidden" name="{{ $name }}[unit]" x-model="unit">
            <div class="ad-half-right" :class="{'is-open': open}" @click="open = !open">
                <span class="ad-dropdown-text" x-text="getUnitLabel()"></span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
                <div class="ad-dropdown-menu" x-show="open" style="display: none;"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    <template x-for="item in units" :key="item.val">
                        <button type="button" class="ad-dropdown-item" :class="{'is-selected': unit === item.val}" @click.stop="selectUnit(item.val)">
                            <template x-if="item.icon === 'clock'">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_1584_4593)">
                                        <path d="M10.0003 18.3333C14.6027 18.3333 18.3337 14.6023 18.3337 9.99996C18.3337 5.39759 14.6027 1.66663 10.0003 1.66663C5.39795 1.66663 1.66699 5.39759 1.66699 9.99996C1.66699 14.6023 5.39795 18.3333 10.0003 18.3333Z" stroke="#BDBCDB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 5V10L13.3333 11.6667" stroke="#BDBCDB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_1584_4593">
                                            <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'calendar'">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.66699 10C1.66699 6.85754 1.66699 5.28587 2.64366 4.31004C3.62033 3.33421 5.19116 3.33337 8.33366 3.33337H11.667C14.8095 3.33337 16.3812 3.33337 17.357 4.31004C18.3328 5.28671 18.3337 6.85754 18.3337 10V11.6667C18.3337 14.8092 18.3337 16.3809 17.357 17.3567C16.3803 18.3325 14.8095 18.3334 11.667 18.3334H8.33366C5.19116 18.3334 3.61949 18.3334 2.64366 17.3567C1.66783 16.38 1.66699 14.8092 1.66699 11.6667V10Z" stroke="#BDBCDB" stroke-width="1.5"/>
                                    <path d="M5.83398 3.33337V2.08337M14.1673 3.33337V2.08337M2.08398 7.50004H17.9173" stroke="#BDBCDB" stroke-width="1.5" stroke-linecap="round"/>
                                    <path d="M15 14.1667C15 14.3877 14.9122 14.5996 14.7559 14.7559C14.5996 14.9122 14.3877 15 14.1667 15C13.9457 15 13.7337 14.9122 13.5774 14.7559C13.4211 14.5996 13.3333 14.3877 13.3333 14.1667C13.3333 13.9457 13.4211 13.7337 13.5774 13.5774C13.7337 13.4211 13.9457 13.3333 14.1667 13.3333C14.3877 13.3333 14.5996 13.4211 14.7559 13.5774C14.9122 13.7337 15 13.9457 15 14.1667ZM15 10.8333C15 11.0543 14.9122 11.2663 14.7559 11.4226C14.5996 11.5789 14.3877 11.6667 14.1667 11.6667C13.9457 11.6667 13.7337 11.5789 13.5774 11.4226C13.4211 11.2663 13.3333 11.0543 13.3333 10.8333C13.3333 10.6123 13.4211 10.4004 13.5774 10.2441C13.7337 10.0878 13.9457 10 14.1667 10C14.3877 10 14.5996 10.0878 14.7559 10.2441C14.9122 10.4004 15 10.6123 15 10.8333ZM10.8333 14.1667C10.8333 14.3877 10.7455 14.5996 10.5893 14.7559C10.433 14.9122 10.221 15 10 15C9.77899 15 9.56702 14.9122 9.41074 14.7559C9.25446 14.5996 9.16667 14.3877 9.16667 14.1667C9.16667 13.9457 9.25446 13.7337 9.41074 13.5774C9.56702 13.4211 9.77899 13.3333 10 13.3333C10.221 13.3333 10.433 13.4211 10.5893 13.5774C10.7455 13.7337 10.8333 13.9457 10.8333 14.1667ZM10.8333 10.8333C10.8333 11.0543 10.7455 11.2663 10.5893 11.4226C10.433 11.5789 10.221 11.6667 10 11.6667C9.77899 11.6667 9.56702 11.5789 9.41074 11.4226C9.25446 11.2663 9.16667 11.0543 9.16667 10.8333C9.16667 10.6123 9.25446 10.4004 9.41074 10.2441C9.56702 10.0878 9.77899 10 10 10C10.221 10 10.433 10.0878 10.5893 10.2441C10.7455 10.4004 10.8333 10.6123 10.8333 10.8333ZM6.66667 14.1667C6.66667 14.3877 6.57887 14.5996 6.42259 14.7559C6.26631 14.9122 6.05435 15 5.83333 15C5.61232 15 5.40036 14.9122 5.24408 14.7559C5.0878 14.5996 5 14.3877 5 14.1667C5 13.9457 5.0878 13.7337 5.24408 13.5774C5.40036 13.4211 5.61232 13.3333 5.83333 13.3333C6.05435 13.3333 6.26631 13.4211 6.42259 13.5774C6.57887 13.7337 6.66667 13.9457 6.66667 14.1667ZM6.66667 10.8333C6.66667 11.0543 6.57887 11.2663 6.42259 11.4226C6.26631 11.5789 6.05435 11.6667 5.83333 11.6667C5.61232 11.6667 5.40036 11.5789 5.24408 11.4226C5.0878 11.2663 5 11.0543 5 10.8333C5 10.6123 5.0878 10.4004 5.24408 10.2441C5.40036 10.0878 5.61232 10 5.83333 10C6.05435 10 6.26631 10.0878 6.42259 10.2441C6.57887 10.4004 6.66667 10.6123 6.66667 10.8333Z" fill="#BDBCDB"/>
                                </svg>
                            </template>
                            <span x-text="item.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
        <div class="ad-hint" x-text="getHintText()"></div>
    </div>
</div>
