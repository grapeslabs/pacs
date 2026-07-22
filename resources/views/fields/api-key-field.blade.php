@php
    /** @var string $fullKey */
    /** @var string $maskedKey */
@endphp

<div class="apikey-cell" x-data="{ revealed: false, full: @js($fullKey), masked: @js($maskedKey) }">
    <span class="apikey-cell__value" x-text="revealed ? full : masked"></span>

    <button type="button" class="apikey-cell__btn"
            @click="revealed = !revealed"
            :title="revealed ? 'Скрыть' : 'Показать'">
        <span x-show="!revealed"><x-moonshine::icon icon="eye" size="5" /></span>
        <span x-show="revealed" x-cloak><x-moonshine::icon icon="eye-slash" size="5" /></span>
    </button>

    <button type="button" class="apikey-cell__btn"
            @click="navigator.clipboard.writeText(full).then(() => window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', text: 'Ключ скопирован' } })))"
            title="Скопировать">
        <x-moonshine::icon icon="document-duplicate" size="5" />
    </button>
</div>

@once
    <style>
        .apikey-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .apikey-cell__value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 14px;
            letter-spacing: 0.02em;
            color: var(--gray-600, #4E4E61);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .apikey-cell__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            color: var(--gray-main, #7F7F9D);
            transition: background 0.15s ease, color 0.15s ease;
        }

        .apikey-cell__btn:hover {
            background: var(--gray-blue-100, #EAEDFB);
            color: var(--gray-600, #4E4E61);
        }

        .apikey-cell__btn svg {
            width: 18px;
            height: 18px;
        }
    </style>
@endonce
