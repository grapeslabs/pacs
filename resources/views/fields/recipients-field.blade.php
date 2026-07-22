@php /** @var string $first */ /** @var int $more */ @endphp

<span class="recipients-cell">
    <svg class="recipients-cell__ic" width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M21 4 3 11l6 2 2 6 4-6 6-9z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
    <span class="recipients-cell__name">{{ $first }}</span>
    @if($more > 0)
        <span class="recipients-cell__more">+{{ $more }}</span>
    @endif
</span>

@once
    <style>
        .recipients-cell { display:inline-flex; align-items:center; gap:7px; min-width:0; }
        .recipients-cell__ic { flex:0 0 auto; color:#8A8AA3; }
        .recipients-cell__name { font-size:14px; color:#22223A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .recipients-cell__more { flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; height:20px; padding:0 7px; border-radius:4px; background:#EEF0F8; color:#5B5B75; font-size:11px; font-weight:700; }
    </style>
@endonce
