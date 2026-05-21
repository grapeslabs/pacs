<style>
    .feature-spoiler-wrapper {
        border-bottom: 1px solid #E5E7EB;
        padding: 12px 0;
        display: flex;
        flex-direction: column;
    }
    .feature-spoiler-wrapper:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .feature-spoiler-wrapper:first-child {
        padding-top: 0;
    }
    .feature-spoiler-header {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        width: 100%;
    }
    .feature-spoiler-switch-wrap {
        margin-right: 16px;
        display: flex;
        align-items: center;
    }
    .feature-spoiler-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 24px;
        flex-shrink: 0;
    }
    .feature-spoiler-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .feature-spoiler-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #E5E7EB;
        transition: background-color 0.3s;
        border-radius: 24px;
    }
    .feature-spoiler-slider:before {
        position: absolute;
        content: '';
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: #FFFFFF;
        transition: transform 0.3s, background-color 0.3s, box-shadow 0.3s;
        border-radius: 50%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }
    .feature-spoiler-switch input:checked + .feature-spoiler-slider {
        background-color: #B8C0FF;
    }
    .feature-spoiler-switch input:checked + .feature-spoiler-slider:before {
        transform: translateX(16px);
        background-color: #6972F0;
        box-shadow: none;
    }
    .feature-spoiler-label {
        flex-grow: 1;
        font-size: 14px;
        font-weight: 500;
        color: #1F2937;
    }
    .feature-spoiler-icon {
        color: #9CA3AF;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .feature-spoiler-icon svg {
        width: 16px;
        height: 16px;
    }
    .feature-spoiler-content {
        margin-top: 18px;
        padding-left: 56px;
    }
    .feature-spoiler-warning {
        font-size: 13px;
        color: #6972F0;
        margin-bottom: 14px;
        font-weight: 400;
    }
    .feature-spoiler-nested {
        transition: opacity 0.3s ease;
    }
    .feature-spoiler-nested.is-disabled {
        opacity: 0.4;
        pointer-events: none;
    }
</style>

<div x-data="{
        isOpen: false,
        isEnabled: {{ $element->getValue() ? 'true' : 'false' }}
     }"
     class="feature-spoiler-wrapper">

    <div class="feature-spoiler-header" @click="isOpen = !isOpen">
        <div class="feature-spoiler-switch-wrap" @click.stop>
            <label class="feature-spoiler-switch">
                <input type="hidden" name="{{ $element->getColumn() }}" value="0">
                <input type="checkbox"
                       name="{{ $element->getColumn() }}"
                       value="1"
                       x-model="isEnabled">
                <span class="feature-spoiler-slider"></span>
            </label>
        </div>

        <div class="feature-spoiler-label">
            {{ $element->getLabel() }}
        </div>

        <div class="feature-spoiler-icon"
             x-bind:style="isOpen ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);'"
             style="transition: transform 0.3s ease;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    <div class="feature-spoiler-content" x-show="isOpen" x-collapse x-cloak>
        <template x-if="!isEnabled">
            <div class="feature-spoiler-warning">
                Включите функцию, чтобы настроить
            </div>
        </template>

        <div class="feature-spoiler-nested" :class="!isEnabled ? 'is-disabled' : ''">
            <x-moonshine::fields-group
                :components="$element->getFields()"
                :container="true"
            />
        </div>
    </div>
</div>
