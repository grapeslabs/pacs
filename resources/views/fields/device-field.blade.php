@php
    /** @var string $text */
    /** @var string|null $subtext */
    /** @var string|null $icon */
    /** @var bool $deleted */
@endphp

<div class="dev-cell{{ $deleted ? ' dev-cell--deleted' : '' }}">
    @if($deleted)
        <span class="dev-cell__ic dev-cell__ic--err">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
        </span>
    @elseif($icon)
        <span class="dev-cell__ic">{!! $icon !!}</span>
    @endif

    <div style="min-width:0;">
        <div class="dev-cell__name">{{ $text }}</div>
        @if($subtext !== null && $subtext !== '')
            <div class="dev-cell__sub">{{ $subtext }}</div>
        @endif
    </div>
</div>

@once
    <style>
        .dev-cell { display:inline-flex; align-items:flex-start; gap:10px; min-width:0; }
        .dev-cell__ic { flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; margin-top:1px; color:#8A8AA3; }
        .dev-cell__ic svg { width:20px; height:20px; }
        .dev-cell__ic--err { color:#E0483C; }
        .dev-cell__name { font-size:14px; font-weight:600; color:#22223A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .dev-cell__sub { font-size:12px; color:#9096AE; margin-top:2px; }
        .dev-cell--deleted .dev-cell__name { color:#AEB3C0; text-decoration:line-through; font-weight:500; }
        .dev-cell--deleted .dev-cell__sub { color:#E0483C; }
    </style>
@endonce
