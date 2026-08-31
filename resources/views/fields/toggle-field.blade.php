@php
    $currentValue = (bool) $field->toValue();
@endphp

<style>
    .tgl-wrapper {
        width: 100%;
        font-family: inherit;
        background: transparent;
        padding: 0;
        border: none;
    }

    .tgl-hidden {
        display: none;
    }

    .tgl-card {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
    }

    .tgl-texts {
        min-width: 0;
    }

    .tgl-label {
        color: #1a1a2e;
        font-weight: 700;
        font-size: 0.9375rem;
        line-height: 1.2;
        display: block;
    }

    .tgl-sub {
        color: #9A9AAC;
        font-size: 0.8125rem;
        margin-top: 1px;
    }

    .tgl-switch {
        position: relative;
        display: inline-flex;
        align-items: center;
        height: 26px;
        width: 46px;
        border-radius: 9999px;
        background-color: #C7CBE0;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: background-color 0.3s ease-in-out;
        flex-shrink: 0;
    }

    .tgl-switch.is-active {
        background-color: #7E92F8;
    }

    .tgl-switch:focus {
        outline: none;
    }

    .tgl-circle {
        display: inline-block;
        height: 22px;
        width: 22px;
        border-radius: 9999px;
        background-color: #ffffff;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, .15);
        transform: translateX(2px);
        transition: transform 0.3s ease-in-out;
    }

    .tgl-switch.is-active .tgl-circle {
        transform: translateX(22px);
    }
</style>

<div x-data="{ value: {{ $currentValue ? 'true' : 'false' }} }" class="tgl-wrapper">
    <input type="hidden" name="{{ $field->getColumn() }}" value="0">
    <input type="checkbox" name="{{ $field->getColumn() }}" value="1" x-model="value" class="tgl-hidden">

    <div class="tgl-card">
        <button type="button" @click="value = !value" :class="{ 'is-active': value }" class="tgl-switch">
            <span class="tgl-circle"></span>
        </button>
        <div class="tgl-texts">
            <span class="tgl-label">{{ $field->getToggleLabel() }}</span>
            @if($field->getToggleSubtitle())
                <div class="tgl-sub">{{ $field->getToggleSubtitle() }}</div>
            @endif
        </div>
    </div>
</div>
