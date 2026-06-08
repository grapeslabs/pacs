<style>
    .feature-checkbox-wrapper {
        display: flex;
        align-items: center;
        margin-bottom: 10px !important;
        cursor: pointer;
    }
    .feature-checkbox-wrapper:last-child {
        margin-bottom: 0;
    }
    .feature-checkbox-input {
        display: none;
    }
    .feature-checkbox-box {
        width: 18px;
        height: 18px;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        background-color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        transition: all 0.15s ease-in-out;
        flex-shrink: 0;
    }
    .feature-checkbox-box svg {
        width: 12px;
        height: 12px;
        color: #FFFFFF;
        opacity: 0;
        transition: opacity 0.15s ease-in-out;
    }
    .feature-checkbox-input:checked + .feature-checkbox-box {
        background-color: #727CF5;
        border-color: #727CF5;
    }
    .feature-checkbox-input:checked + .feature-checkbox-box svg {
        opacity: 1;
    }
    .feature-checkbox-label {
        font-size: 14px;
        color: #1F2937;
        font-weight: 400;
        user-select: none;
    }
</style>

<label class="feature-checkbox-wrapper">
    <input type="hidden" name="{{ $element->getColumn() }}" value="0">
    <input type="checkbox"
           name="{{ $element->getColumn() }}"
           value="1"
           class="feature-checkbox-input"
           @change="onChangeField($event)"
           @if((string) $element->getValue() === '1' || $element->getValue() === true) checked @endif
    >
    <div class="feature-checkbox-box">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
        </svg>
    </div>
    <div class="feature-checkbox-label">
        {{ $element->getLabel() }}
    </div>
</label>
