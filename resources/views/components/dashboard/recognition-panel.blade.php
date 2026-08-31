<div {{ $attributes }}
     style="display:flex;flex-direction:column;height:350px;box-sizing:border-box;border:1px solid #e3e7f5;background:#fff;border-radius:16px;box-shadow:0 1px 2px rgba(127,127,157,.05), 0 10px 30px rgba(127,127,157,.10);overflow:hidden;">
    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e3e7f5;">
        <h2 style="margin:0;font-size:16px;font-weight:800;color:#1b1a2a;">Распознавание лиц</h2>
        <span style="padding:4px 10px;border-radius:4px;font-size:0.75rem;font-weight:500;background:#eef1ff;color:#6e63e6;">
            {{ $camerasCount }} камер
        </span>
    </div>

    <div style="flex:1;min-height:0;overflow-y:auto;">
    @forelse($items as $c)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;{{ !$loop->last ? 'border-bottom:1px solid #eef1f7;' : '' }}">
            <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;flex-shrink:0;background:#faf9f5;color:#6e63e6;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h9A1.5 1.5 0 0 1 15 7.5v9A1.5 1.5 0 0 1 13.5 18h-9A1.5 1.5 0 0 1 3 16.5z"></path><path d="M15 10.5 21 7v10l-6-3.5"></path></svg>
            </span>
            <div style="min-width:0;flex:1;">
                <div style="display:flex;align-items:center;gap:8px;font-size:0.875rem;font-weight:500;color:#1b1a2a;white-space:nowrap;overflow:hidden;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:#2f8f4e;box-shadow:0 0 0 3px rgba(47,143,78,0.18);flex-shrink:0;"></span>
                    <span style="overflow:hidden;text-overflow:ellipsis;">{{ $c['camera'] }}</span>
                </div>
                <div style="font-size:0.75rem;color:#6b7088;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $c['location'] }}</div>
            </div>
            <div style="flex-shrink:0;text-align:right;">
                <div style="font-size:1rem;font-weight:700;color:#1b1a2a;font-variant-numeric:tabular-nums;">{{ number_format($c['count'], 0, '.', ' ') }}</div>
                <div style="font-size:0.75rem;color:#6b7088;">за сутки</div>
            </div>
        </div>
    @empty
        <div style="padding:40px 20px;text-align:center;font-size:0.875rem;color:#6b7088;">Распознаваний за сутки нет</div>
    @endforelse
    </div>

    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-top:1px solid #e3e7f5;">
        <span style="font-size:0.875rem;color:#6b7088;">Всего распознано</span>
        <span style="font-size:1.1rem;font-weight:700;color:#1b1a2a;font-variant-numeric:tabular-nums;">{{ number_format($total, 0, '.', ' ') }}</span>
    </div>
</div>
