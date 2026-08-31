@once
    <style>
        @media (max-width: 1024px) {
            .qa-label { display: none !important; }
            .qa-btn { justify-content: center !important; padding: 14px !important; }
        }
        .qa-ico svg { width: 16px !important; height: 16px !important; display: block; }
    </style>
@endonce
<div {{ $attributes }}
     style="display:flex;flex-direction:column;height:100%;box-sizing:border-box;
            border:1px solid #e3e7f5;background:#fff;border-radius:18px;padding:20px;
            box-shadow:0 1px 2px rgba(127,127,157,.05), 0 10px 30px rgba(127,127,157,.10);">
    <h2 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#1b1a2a;">Быстрые действия</h2>
    <div style="flex:1;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        @foreach($actions as $a)
            <a href="{{ $a['url'] }}" class="qa-btn"
               style="display:flex;align-items:center;justify-content:space-between;gap:8px;text-decoration:none;
                      border-radius:16px;padding:16px;color:#fff;
                      background:linear-gradient(135deg,#7e92f8 0%,#6e63e6 100%);
                      box-shadow:0 1px 3px rgba(27,26,42,0.10);">
                <span style="display:flex;align-items:center;gap:10px;">
                    <span class="qa-ico" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.18);flex-shrink:0;color:#fff;">
                        {!! $a['icon'] !!}
                    </span>
                    <span class="qa-label" style="font-size:0.875rem;font-weight:500;line-height:1.2;">{{ $a['label'] }}</span>
                </span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;opacity:0.85;">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </a>
        @endforeach
    </div>
</div>
