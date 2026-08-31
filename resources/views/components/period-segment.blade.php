@php
    /** @var array $periods       элементы: ['key','label','url','active','custom'] */
    /** @var bool  $customActive   выбран режим «Период» */
    /** @var string $from          значение «с» (YYYY-MM-DD) */
    /** @var string $to            значение «по» (YYYY-MM-DD) */
    $periods = $periods ?? [];
    $customActive = $customActive ?? false;
    $from = $from ?? '';
    $to = $to ?? '';
@endphp

<div class="pseg-wrap">
    <div class="pseg">
        @foreach($periods as $p)
            <a href="{{ $p['url'] }}" class="pseg__btn {{ $p['active'] ? 'is-on' : '' }}">{{ $p['label'] }}</a>
        @endforeach
    </div>

    @if($customActive)
        <div class="pseg-range"
             x-data="{
                from: @js($from),
                to: @js($to),
                apply() {
                    const u = new URL(window.location);
                    u.searchParams.set('period', 'custom');
                    u.searchParams.set('page', '1');
                    this.from ? u.searchParams.set('from', this.from) : u.searchParams.delete('from');
                    this.to ? u.searchParams.set('to', this.to) : u.searchParams.delete('to');
                    window.location = u.toString();
                }
             }">
            <input type="date" class="pseg-date" x-model="from" @change="apply()" aria-label="Дата с">
            <input type="date" class="pseg-date" x-model="to" @change="apply()" aria-label="Дата по">
        </div>
    @endif
</div>

@once
<style>
    .pseg-wrap { display: inline-flex; align-items: center; gap: 10px; }
    .pseg {
        display: inline-flex;
        background: #F1F3FB;
        border: 1px solid #D7DBEF;
        border-radius: 10px;
        padding: 3px;
        gap: 2px;
    }
    .pseg__btn {
        border: 0; background: transparent;
        padding: 7px 16px; border-radius: 7px;
        font-weight: 700; font-size: 13px; line-height: 1;
        color: #66667D; cursor: pointer; white-space: nowrap;
        text-decoration: none;
        display: inline-flex; align-items: center;
        transition: all .15s;
    }
    .pseg__btn:hover { color: #1E1E1E; }
    .pseg__btn.is-on {
        background: #fff; color: #1E1E1E;
        box-shadow: 0 2px 8px rgba(127,127,157,.08);
    }

    .pseg-range { display: inline-flex; align-items: center; gap: 8px; }
    .pseg-date {
        width: 160px; height: 42px;
        padding: 0 12px;
        border: 1.5px solid #D7DBEF; border-radius: 12px;
        background: #fff;
        font-family: inherit; font-weight: 700; font-size: 14px;
        color: #1E1E1E; font-variant-numeric: tabular-nums;
    }
    .pseg-date:focus {
        outline: none; border-color: #7381F4;
        box-shadow: 0 0 0 3px rgba(115,129,244,.18);
    }
    .pseg-date::-webkit-calendar-picker-indicator { cursor: pointer; opacity: .55; }
</style>
@endonce
