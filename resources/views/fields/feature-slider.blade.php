<style>
    .feature-slider-wrapper {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
        padding-left: 30px;
    }
    .feature-slider-wrapper:last-child {
        margin-bottom: 0;
    }
    .feature-slider-label {
        font-size: 12px;
        color: #7F7F9D;
        margin-right: 16px;
        white-space: nowrap;
    }
    .feature-slider-range-container {
        flex-grow: 1;
        display: flex;
        align-items: center;
        margin-right: 16px;
        max-width: 210px;
    }
    .feature-slider-input[type=range] {
        -webkit-appearance: none;
        width: 100%;
        height: 6px;
        border-radius: 8px;
        outline: none;
        background-color: transparent;
    }
    .feature-slider-input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #FFFFFF;
        border: 2px solid #7381F4;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .feature-slider-input[type=range]::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #FFFFFF;
        border: 2px solid #7381F4;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .feature-slider-number {
        width: 36px;
        height: 24px;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        text-align: center;
        font-size: 12px;
        color: #4B5563;
        outline: none;
        background-color: transparent;
        padding: 0;
    }
    .feature-slider-number:focus {
        border-color: #7381F4;
    }
    .feature-slider-number::-webkit-outer-spin-button,
    .feature-slider-number::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .feature-slider-number[type=number] {
        -moz-appearance: textfield;
    }
</style>

<div class="feature-slider-wrapper moonshine-field"
     x-data="{
        value: {{ (int) ($element->getValue() ?? $element->getMin()) }},
        min: {{ $element->getMin() }},
        max: {{ $element->getMax() }},
        get trackStyle() {
            let percentage = ((this.value - this.min) / (this.max - this.min)) * 100;
            return `background: linear-gradient(to right, #7381F4 0%, #7381F4 ${percentage}%, #F3F4F6 ${percentage}%, #F3F4F6 100%);`;
        }
     }">

    <div class="feature-slider-label">
        {{ $element->getLabel() }}
    </div>

    <div class="feature-slider-range-container">
        <input type="range"
               x-model="value"
               min="{{ $element->getMin() }}"
               max="{{ $element->getMax() }}"
               step="{{ $element->getStep() }}"
               class="feature-slider-input"
               x-bind:style="trackStyle"
        >
    </div>

    <input type="number"
           name="{{ $element->getColumn() }}"
           x-model="value"
           min="{{ $element->getMin() }}"
           max="{{ $element->getMax() }}"
           step="{{ $element->getStep() }}"
           class="feature-slider-number"
    >
</div>
