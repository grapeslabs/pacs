@props([
    'variant' => 'edit',
    'url' => '#',
])

@php
    $icons = [
        'play' => [
            'viewBox' => '0 0 20 20',
            'paths' => '<path d="M5.83301 4.16669L15.833 10L5.83301 15.8334V4.16669Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        'edit' => [
            'viewBox' => '0 0 24 24',
            'paths' => '<path d="M4 20h4L18.5 9.5a2 2 0 0 0-2.8-2.8L5 17.2z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>'
                . '<path d="M14 8l2 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        'delete' => [
            'viewBox' => '0 0 24 24',
            'paths' => '<path d="M4 7h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>'
                . '<path d="M10 11v6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>'
                . '<path d="M14 11v6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>'
                . '<path d="M5.5 7l1 12.5a1.5 1.5 0 0 0 1.5 1.4h8a1.5 1.5 0 0 0 1.5-1.4L18.5 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>'
                . '<path d="M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
    ];
    $icon = $icons[$variant] ?? $icons['edit'];
@endphp

<a href="{{ $url }}" {{ $attributes->class('action-icon-btn') }}>
    <svg width="20" height="20" viewBox="{{ $icon['viewBox'] }}" fill="none" xmlns="http://www.w3.org/2000/svg">
        {!! $icon['paths'] !!}
    </svg>
</a>

@once
<style>
.action-icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; padding: 0;
    border: none; background: transparent; border-radius: 8px;
    color: #BDBCDB; cursor: pointer; transition: color .15s, background .15s;
}
.action-icon-btn:hover { color: #7885f7; background: #EEF0FB; }
.action-icon-btn svg { width: 20px; height: 20px; flex-shrink: 0; }
</style>
@endonce
