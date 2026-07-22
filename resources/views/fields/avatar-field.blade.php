@php
    /** @var string $text */
    /** @var string|null $subtext */
    /** @var string|null $photoUrl */
    /** @var string|null $icon */
    /** @var string|null $badge */
    /** @var string $initials */
@endphp

<div class="av-cell">
    <span class="av-cell__ava{{ $photoUrl ? ' av-cell__ava--img' : ($icon ? ' av-cell__ava--icon' : '') }}">
        @if($photoUrl)
            <img src="{{ $photoUrl }}" alt="" loading="lazy">
        @elseif($icon)
            {!! $icon !!}
        @else
            {{ $initials }}
        @endif
    </span>

    <span class="av-cell__body">
        <span class="av-cell__title">
            <span class="av-cell__name">{{ $text }}</span>
            @if(!empty($badge))
                <span class="av-cell__badge" title="Доступ заморожен">{!! $badge !!}</span>
            @endif
        </span>
        @if($subtext !== null && $subtext !== '')
            <span class="av-cell__sub">{{ $subtext }}</span>
        @endif
    </span>
</div>

@once
    <style>
        .av-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .av-cell__ava {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            color: var(--white-main, #fff);
            background: linear-gradient(180deg, #7E92F8, #6972F0);
        }

        .av-cell__ava--img,
        .av-cell__ava--icon {
            background: var(--gray-blue-100, #EAEDFB);
        }

        .av-cell__ava--icon {
            color: var(--gray-500, #666070);
        }

        .av-cell__ava img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .av-cell__ava svg {
            width: 20px;
            height: 20px;
        }

        .av-cell__body {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .av-cell__title {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: 14px;
            line-height: 1.25;
            color: var(--dark-main, #1E1E1E);
            min-width: 0;
        }

        .av-cell__name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .av-cell__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: #F5820B;
        }

        .av-cell__badge svg {
            width: 15px;
            height: 15px;
        }

        .av-cell__sub {
            margin-top: 2px;
            font-weight: 600;
            font-size: 12px;
            line-height: 1.2;
            color: var(--gray-main, #7F7F9D);
        }
    </style>
@endonce
