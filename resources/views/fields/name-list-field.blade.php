@php
    /** @var array $names */
@endphp

<div class="namelist-cell">
    @foreach($names as $name)
        <span class="namelist-cell__row">{{ $name }}</span>
    @endforeach
</div>

@once
    <style>
        .namelist-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .namelist-cell__row {
            font-weight: 600;
            font-size: 13px;
            line-height: 1.3;
            color: var(--dark-main, #1E1E1E);
            white-space: nowrap;
        }
    </style>
@endonce
