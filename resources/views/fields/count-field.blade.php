@php /** @var int $count */ @endphp

@if($count > 0)
    <span class="count-badge">{{ $count }}</span>
@else
    <span class="count-empty">—</span>
@endif

@once
    <style>
        .count-badge { display:inline-flex; align-items:center; justify-content:center; min-width:26px; height:24px; padding:0 8px; border-radius:4px; background:#EAEDF8; color:#4E4E61; font-size:12px; font-weight:700; }
        .count-empty { color:#B9BEC9; }
    </style>
@endonce
