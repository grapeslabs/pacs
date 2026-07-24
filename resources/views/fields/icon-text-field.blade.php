@php
    /** @var string $text */
    /** @var string $icon */
    /** @var bool $deleted */
    $deleted = $deleted ?? false;
@endphp

<span class="ictx-cell{{ $deleted ? ' ictx-cell--deleted' : '' }}">
    <span class="ictx-cell__ico">{!! $icon !!}</span>
    <span class="ictx-cell__txt">{{ $text }}</span>
</span>

@once
    <style>
        .ictx-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            padding-top: 13px;
            padding-bottom: 13px;
        }

        .ictx-cell__ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: #9AA0B4;
        }

        .ictx-cell__ico svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .ictx-cell__txt {
            font-size: 14px;
            font-weight: 600;
            color: #6B7390;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ictx-cell--deleted .ictx-cell__txt {
            text-decoration: line-through;
            color: #A6ABC2;
        }
    </style>
@endonce
