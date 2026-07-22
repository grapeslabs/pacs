@php /** @var int $count */ @endphp

@if($count > 0)
    <span class="count-badge">{{ $count }}</span>
@else
    <span class="count-empty">—</span>
@endif

@once
    <style>
        .count-badge { display:inline-flex; align-items:center; justify-content:center; min-width:26px; height:24px; padding:0 8px; border-radius:4px; background:#EAEDF8; color:#66667D; font-size:13px; font-weight:600; }
        .count-empty { color:#B9BEC9; }
    </style>
@endonce
