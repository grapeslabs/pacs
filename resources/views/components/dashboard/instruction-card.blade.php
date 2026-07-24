<div {{ $attributes }}
     style="border:1px solid #e3e7f5;background:#fff;border-radius:16px;box-shadow:0 1px 2px rgba(127,127,157,.05), 0 10px 30px rgba(127,127,157,.10);overflow:hidden;">
    <img src="/images/guide.png" alt="{{ $title }}"
         style="display:block;width:100%;height:140px;object-fit:cover;">

    <div style="padding:20px;">
        <h3 style="margin:0;font-size:1rem;font-weight:600;color:#1b1a2a;">{{ $title }}</h3>
        <p style="margin:6px 0 0;font-size:0.875rem;line-height:1.5;color:#6b7088;">{{ $description }}</p>
        @if($btnUrl)
            <a href="{{ $btnUrl }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:8px;margin-top:16px;background:#7E92F8;color:#fff;
                      padding:10px 18px;border-radius:24px;font-size:0.875rem;font-weight:700;text-decoration:none;">
                {{ $btnText }}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        @endif
    </div>
</div>
