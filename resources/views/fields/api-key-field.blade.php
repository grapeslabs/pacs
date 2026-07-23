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
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 15C9 12.1716 9 10.7574 9.87868 9.87868C10.7574 9 12.1716 9 15 9L16 9C18.8284 9 20.2426 9 21.1213 9.87868C22 10.7574 22 12.1716 22 15V16C22 18.8284 22 20.2426 21.1213 21.1213C20.2426 22 18.8284 22 16 22H15C12.1716 22 10.7574 22 9.87868 21.1213C9 20.2426 9 18.8284 9 16L9 15Z"></path><path d="M16.9999 9C16.9975 6.04291 16.9528 4.51121 16.092 3.46243C15.9258 3.25989 15.7401 3.07418 15.5376 2.90796C14.4312 2 12.7875 2 9.5 2C6.21252 2 4.56878 2 3.46243 2.90796C3.25989 3.07417 3.07418 3.25989 2.90796 3.46243C2 4.56878 2 6.21252 2 9.5C2 12.7875 2 14.4312 2.90796 15.5376C3.07417 15.7401 3.25989 15.9258 3.46243 16.092C4.51121 16.9528 6.04291 16.9975 9 16.9999"></path></svg>
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
