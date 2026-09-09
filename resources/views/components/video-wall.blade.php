@php
    $payload = [
        'walls' => $walls,
        'streams' => $streams,
        'state' => $state + ['csrf' => csrf_token()],
    ];
@endphp

<div id="videowall" data-payload="{{ json_encode($payload, JSON_UNESCAPED_UNICODE) }}">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <div style="min-width:0;">
            <h2 style="font-size:22px;font-weight:700;color:#1a1a2e;margin:0;line-height:1.2;">Видеостены</h2>
            <p style="font-size:13px;color:#7F7F9D;margin:4px 0 0;">Загрузка…</p>
        </div>
    </div>

    <div style="aspect-ratio:16/9;width:100%;border-radius:16px;background:#1E1E1E;box-shadow:0 8px 24px rgba(127,127,157,.10);"></div>
</div>

@vite(['resources/js/videowall.js'])
