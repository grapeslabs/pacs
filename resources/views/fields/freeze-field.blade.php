@php
    /** @var array $reasons */
    /** @var array $reasonKeys */
    /** @var string $otherIcon */
    /** @var string|null $start */
    /** @var string|null $end */
    /** @var string|null $reason */
    /** @var bool $enabled */
    $customReason = $reason !== null && !in_array($reason, $reasonKeys, true);
@endphp

<div
    x-data="{
        enabled: {{ $enabled ? 'true' : 'false' }},
        reason: @js($reason ?? ''),
        presets: @js($reasonKeys),
        showCustom: {{ $customReason ? 'true' : 'false' }},
        pick(r) { this.reason = r; this.showCustom = false; },
        pickCustom() {
            this.showCustom = true;
            if (this.presets.includes(this.reason)) { this.reason = ''; }
        },
        isActive(r) { return !this.showCustom && this.reason === r; }
    }"
    class="frz"
>
    <input type="hidden" name="frozen_enabled" :value="enabled ? 1 : 0">
    <input type="hidden" name="frozen_reason" :value="reason">

    <div class="frz__head" @click="enabled = !enabled">
        <span class="frz__icon">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M4.5 6l15 12M19.5 6l-15 12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M12 6.5 9.5 5m2.5 1.5L14.5 5M12 17.5 9.5 19m2.5-1.5L14.5 19M5.5 8.8 5 6m.5 2.8L3 9.4M18.5 8.8 19 6m-.5 2.8L21 9.4M5.5 15.2 5 18m.5-2.8L3 14.6M18.5 15.2 19 18m-.5-2.8L21 14.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div class="frz__head-text">
            <div class="frz__title">Заморозка доступа</div>
            <div class="frz__subtitle">Временно запретить проход без удаления персоны и ключей</div>
        </div>
        <label class="frz__switch" @click.stop>
            <input type="checkbox" x-model="enabled">
            <span class="frz__slider"></span>
        </label>
    </div>

    <div class="frz__body" x-show="enabled" x-collapse x-cloak>
        <div class="frz__grid">
            <div class="frz__field">
                <label class="frz__label">Заморозить с</label>
                <div class="frz__input-wrap">
                    <input type="date" name="frozen_start" value="{{ $start }}" class="frz__input">
                    <span class="frz__cal">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M3.5 9.5h17M8 3.5v3M16 3.5v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                </div>
                <div class="frz__hint">Оставьте пустым — сразу</div>
            </div>
            <div class="frz__field">
                <label class="frz__label">Заморозить до</label>
                <div class="frz__input-wrap">
                    <input type="date" name="frozen_end" value="{{ $end }}" class="frz__input">
                    <span class="frz__cal">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M3.5 9.5h17M8 3.5v3M16 3.5v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                </div>
                <div class="frz__hint">Оставьте пустым — бессрочно</div>
            </div>
        </div>

        <div class="frz__reason">
            <div class="frz__label">Причина заморозки</div>
            <div class="frz__chips">
                @foreach($reasons as $r)
                    <button type="button" class="frz__chip" :class="isActive(@js($r['label'])) ? 'is-active' : ''" @click="pick(@js($r['label']))">
                        @if($r['icon'])<span class="frz__chip-ic">{!! $r['icon'] !!}</span>@endif
                        {{ $r['label'] }}
                    </button>
                @endforeach
                <button type="button" class="frz__chip" :class="showCustom ? 'is-active' : ''" @click="pickCustom()">
                    @if($otherIcon)<span class="frz__chip-ic">{!! $otherIcon !!}</span>@endif
                    Другое
                </button>
            </div>
            <div class="frz__custom" x-show="showCustom" x-cloak>
                <input type="text" x-model="reason" placeholder="Укажите причину" class="frz__input">
            </div>
        </div>

        <div class="frz__note">
            <span class="frz__note-ic">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.5"/><path d="M12 11v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="7.8" r="1" fill="currentColor"/></svg>
            </span>
            <span>Все ключи и карты персоны не сработают, проход будет отклонён в указанный период.</span>
        </div>
    </div>
</div>

@once
<style>
    .frz {
        border: 1px solid #E1E4F3;
        background: #F4F5FC;
        border-radius: 16px;
        padding: 18px 20px;
    }
    .frz__head {
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        user-select: none;
    }
    .frz__icon {
        flex: 0 0 auto;
        width: 40px; height: 40px;
        border-radius: 12px;
        background: #FFFFFF;
        display: flex; align-items: center; justify-content: center;
        color: #6972F0;
        box-shadow: 0 1px 2px rgba(41,45,60,.06);
    }
    .frz__icon svg { width: 22px; height: 22px; }
    .frz__head-text { flex: 1; min-width: 0; }
    .frz__title { font-size: 15px; font-weight: 700; color: #1F2430; }
    .frz__subtitle { font-size: 12.5px; color: #8A8FA6; margin-top: 2px; line-height: 1.35; }

    .frz__switch { position: relative; display: inline-block; width: 46px; height: 26px; flex-shrink: 0; }
    .frz__switch input { opacity: 0; width: 0; height: 0; position: absolute; }
    .frz__slider {
        position: absolute; inset: 0; cursor: pointer;
        background: #D3D7E8; border-radius: 26px; transition: background .3s;
    }
    .frz__slider:before {
        content: ''; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px;
        background: #fff; border-radius: 50%; transition: transform .3s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .frz__switch input:checked + .frz__slider { background: #6972F0; }
    .frz__switch input:checked + .frz__slider:before { transform: translateX(20px); }

    .frz__body { margin-top: 18px; padding-top: 18px; border-top: 1px solid #E4E7F4; }
    .frz__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 560px) { .frz__grid { grid-template-columns: 1fr; } }

    .frz__label { font-size: 13px; font-weight: 600; color: #5B6072; margin-bottom: 7px; }
    .frz__input-wrap { position: relative; }
    .frz__input {
        width: 100%; box-sizing: border-box;
        height: 44px; padding: 0 14px;
        border: 1px solid #E1E4F3; border-radius: 12px;
        background: #fff; font-size: 14px; color: #2A2F3B;
        transition: border-color .15s, box-shadow .15s;
    }
    .frz__input:focus { outline: none; border-color: #8A93FF; box-shadow: 0 0 0 3px rgba(115,129,244,.2); }
    /* Дата-инпуты: место под кастомную иконку + прячем нативный индикатор
       (чтобы не было двойного календаря), но оставляем его кликабельным поверх иконки. */
    .frz__input[type="date"] { padding-right: 40px; }
    .frz__input[type="date"]::-webkit-calendar-picker-indicator {
        position: absolute; right: 0; top: 0; width: 42px; height: 100%;
        margin: 0; padding: 0; opacity: 0; cursor: pointer;
    }
    .frz__cal {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        color: #A1A5B7; pointer-events: none; display: flex;
    }
    .frz__cal svg { width: 18px; height: 18px; }
    .frz__hint { font-size: 12px; color: #A1A5B7; margin-top: 6px; }

    .frz__reason { margin-top: 18px; }
    .frz__chips { display: flex; flex-wrap: wrap; gap: 10px; }
    .frz__chip {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 16px; border-radius: 12px;
        border: 1px solid #E1E4F3; background: #fff;
        font-size: 13.5px; font-weight: 600; color: #4A4F62; cursor: pointer;
        transition: all .15s;
    }
    .frz__chip:hover { border-color: #B8C0FF; }
    .frz__chip.is-active { border-color: #6972F0; background: #EEF0FF; color: #4049C8; }
    .frz__chip-ic { display: inline-flex; align-items: center; color: #6972F0; }
    .frz__chip-ic svg { width: 16px; height: 16px; }
    .frz__custom { margin-top: 12px; }

    .frz__note {
        display: flex; align-items: flex-start; gap: 10px;
        margin-top: 18px; padding: 14px 16px;
        background: #fff; border: 1px solid #E4E7F4; border-radius: 12px;
        font-size: 13px; color: #6B7085; line-height: 1.45;
    }
    .frz__note-ic { flex: 0 0 auto; color: #8A8FA6; display: flex; margin-top: 1px; }
    .frz__note-ic svg { width: 18px; height: 18px; }
</style>
@endonce
