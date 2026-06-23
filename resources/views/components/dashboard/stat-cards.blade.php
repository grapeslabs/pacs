@php
    $trendStyles = [
        'up'   => 'background:#eaf7ee;color:#2f8f4e;',
        'down' => 'background:#fbf0ef;color:#f04138;',
        'none' => 'background:#faf9f5;color:#6b7088;',
    ];
@endphp
<div {{ $attributes }}
     style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-top:20px;">
    @foreach($cards as $c)
        @php $dir = $c['trend']['dir'] ?? 'none'; @endphp
        <a @if(!empty($c['url'])) href="{{ $c['url'] }}" @endif
           style="position:relative;display:block;text-decoration:none;border:1px solid #e3e7f5;background:#fff;
                  border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(27,26,42,0.05);">
            <span style="position:absolute;right:14px;top:14px;display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:#eef1ff;color:#8b90b0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                    <path d="M7 17 17 7M9 7h8v8"/>
                </svg>
            </span>
            <div style="font-size:1.85rem;font-weight:700;letter-spacing:-0.02em;color:#1b1a2a;font-variant-numeric:tabular-nums;">
                {{ is_numeric($c['value']) ? number_format((int) $c['value'], 0, '.', ' ') : $c['value'] }}
            </div>
            <div style="margin-top:4px;font-size:0.875rem;color:#6b7088;">{{ $c['label'] }}</div>
            <span style="display:inline-flex;align-items:center;gap:4px;margin-top:12px;padding:4px 8px;border-radius:9999px;
                         font-size:0.75rem;font-weight:500;{{ $trendStyles[$dir] }}">
                @if($dir === 'up')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                @elseif($dir === 'down')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                @endif
                {{ $c['trend']['text'] ?? '' }}
            </span>
        </a>
    @endforeach
</div>
