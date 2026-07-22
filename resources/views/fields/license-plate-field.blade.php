@php
    /** @var string $plate */
    /** @var string $main */
    /** @var string|null $region */
@endphp

<span class="plate" title="{{ $plate }}">
    <span class="plate__main">{{ $main }}</span>
    @if($region !== null)
        <span class="plate__region">
            <span class="plate__region-num">{{ $region }}</span>
            <span class="plate__rus">
                <span class="plate__rus-text">RUS</span>
                <span class="plate__flag" aria-hidden="true">
                    <i class="plate__flag-w"></i>
                    <i class="plate__flag-b"></i>
                    <i class="plate__flag-r"></i>
                </span>
            </span>
        </span>
    @endif
</span>

@once
    <style>
        .plate {
            display: inline-flex;
            align-items: stretch;
            height: 32px;
            background: #fff;
            border: 1.5px solid #1b1b28;
            border-radius: 5px;
            overflow: hidden;
            font-family: 'Arial Narrow', 'Roboto Condensed', Arial, sans-serif;
            line-height: 1;
            white-space: nowrap;
        }

        .plate__main {
            display: flex;
            align-items: center;
            padding: 0 9px;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #1b1b28;
        }

        .plate__region {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1px;
            padding: 0 7px;
            border-left: 1.5px solid #1b1b28;
            min-width: 34px;
        }

        .plate__region-num {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #1b1b28;
        }

        .plate__rus {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .plate__rus-text {
            font-size: 7px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #1b1b28;
        }

        .plate__flag {
            display: flex;
            flex-direction: column;
            width: 11px;
            height: 7px;
            border: 0.5px solid rgba(0, 0, 0, 0.2);
            border-radius: 1px;
            overflow: hidden;
        }

        .plate__flag i {
            display: block;
            flex: 1 1 33.33%;
        }

        .plate__flag-w { background: #ffffff; }
        .plate__flag-b { background: #0039A6; }
        .plate__flag-r { background: #D52B1E; }
    </style>
@endonce
