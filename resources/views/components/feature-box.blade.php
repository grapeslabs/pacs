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
