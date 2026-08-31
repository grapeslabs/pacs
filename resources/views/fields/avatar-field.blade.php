@php
    /** @var string $text */
    /** @var string|null $subtext */
    /** @var string|null $photoUrl */
    /** @var string|null $icon */
    /** @var string|null $badge */
    /** @var string $initials */
    /** @var bool $deleted */
    /** @var bool $hideAvatar */
    $deleted = $deleted ?? false;
    $hideAvatar = $hideAvatar ?? false;
@endphp

<div class="av-cell{{ $deleted ? ' av-cell--deleted' : '' }}">
    @unless($hideAvatar)
    <span class="av-cell__ava{{ $deleted ? ' av-cell__ava--deleted' : ($photoUrl ? ' av-cell__ava--img' : ($icon ? ' av-cell__ava--icon' : '')) }}">
        @if($deleted)
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
        @elseif($photoUrl)
            <img src="{{ $photoUrl }}" alt="" loading="lazy">
        @elseif($icon)
            {!! $icon !!}
        @else
            {{ $initials }}
        @endif
    </span>
    @endunless

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
            padding-top: 13px;
            padding-bottom: 13px;
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
            color: #fff !important;
            background: linear-gradient(180deg, #7E92F8, #6972F0);
        }

        .av-cell__ava--img,
        .av-cell__ava--icon {
            background: var(--gray-blue-100, #EAEDF8);
        }

        .av-cell__ava--icon {
            color: #66667D !important;
        }

        .av-cell__ava--deleted {
            background: #FBECEC;
            color: #F04138 !important;
        }

        .av-cell--deleted .av-cell__name {
            text-decoration: line-through;
            color: #A6ABC2;
            font-weight: 500;
        }

        .av-cell--deleted .av-cell__sub {
            color: #F04138;
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
            color: #D47F00;
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
            color: #6B7390;
        }
    </style>
@endonce
