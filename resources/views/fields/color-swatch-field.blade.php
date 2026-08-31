@php
    /** @var string $name */
    /** @var string $hex */
@endphp

<span class="color-swatch">
    <span class="color-swatch__box" style="background: {{ $hex }};"></span>
    <span class="color-swatch__name">{{ $name }}</span>
</span>

@once
    <style>
        .color-swatch { display:inline-flex; align-items:center; gap:8px; }
        .color-swatch__box { width:14px; height:14px; border-radius:4px; border:1px solid rgba(0,0,0,.15); flex:0 0 14px; }
        .color-swatch__name { font-size:14px; color:#22223A; }
    </style>
@endonce
