<style>
    .feature-box-wrapper label.form-label,
    .feature-box-wrapper .form-group-header,
    .feature-box-wrapper label:not([class*="feature-"]) {
        display: none !important;
    }

    .feature-box-wrapper {
        background-color: #F4F6FB;
        border-radius: 16px;
        padding: 12px;
        margin-bottom: 24px;
        font-family: inherit;
    }
    .feature-box-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .feature-box-header .feature-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        color: #6972F0;
    }
    .feature-box-header .feature-icon svg {
        width: 100%;
        height: 100%;
    }
    .feature-box-header .feature-title {
        font-size: 16px;
        font-weight: 500;
        color: #6972F0;
        margin: 0;
    }
    .feature-box-content {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
</style>

@php
    $hasCondition = $element->hasShowWhen();
    if ($hasCondition) {
        $cond = $element->getShowWhenData();
        $condColumn = $cond['column'];
        $condExpected = $cond['value'] ? 'true' : 'false';
    }
@endphp

@if($hasCondition)
<div
    x-data="{
        show: false,
        init() {
            this.$nextTick(() => {
                const form = this.$el.closest('form');
                if (!form) return;
                const cb = form.querySelector('input[type=checkbox][name=\'{{ $condColumn }}\']');
                if (cb) this.show = cb.checked === {{ $condExpected }};
            });
        }
    }"
    @feature-field-change.window="if ($event.detail.column === '{{ $condColumn }}') show = ($event.detail.value === {{ $condExpected }})"
    x-show="show"
    style="display: none;"
>
@endif

<div class="feature-box-wrapper">
    <div class="feature-box-header">
        @if($element->getIcon())
            <div class="feature-icon">
                {!! $element->getIcon() !!}
            </div>
        @endif
        <div class="feature-title">{{ $element->getName() }}</div>
    </div>
    <div class="feature-box-content">
        <x-moonshine::fields-group
            :components="$element->getFields()"
            :container="true"
        />
    </div>
</div>

@if($hasCondition)
</div>
@endif
