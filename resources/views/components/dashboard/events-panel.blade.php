@php
    $initials = function (string $title) {
        $name = trim(preg_replace('/^Распознан:\s*/u', '', $title));
        if ($name === '' || str_contains($name, 'Неопознан')) return '?';
        $parts = preg_split('/\s+/u', $name);
        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    };
@endphp
<div {{ $attributes }} style="position:relative;height:100%;min-height:360px;">
<div style="position:absolute;inset:0;display:flex;flex-direction:column;border:1px solid #e3e7f5;background:#fff;
            border-radius:16px;box-shadow:0 1px 3px rgba(27,26,42,0.05);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e3e7f5;">
        <h2 style="margin:0;font-size:1rem;font-weight:600;color:#1b1a2a;">События</h2>
        @if($allUrl)
            <a href="{{ $allUrl }}" style="display:inline-flex;align-items:center;gap:4px;font-size:0.875rem;font-weight:500;color:#6e63e6;text-decoration:none;">
                Все отчёты
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @endif
    </div>

    <div style="flex:1;min-height:0;overflow-y:auto;">
    @forelse($events as $e)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;{{ !$loop->last ? 'border-bottom:1px solid #eef1f7;' : '' }}">
            @if(!empty($e['avatar']))
                <img src="{{ $e['avatar'] }}" alt="" style="width:44px;height:44px;border-radius:12px;object-fit:cover;flex-shrink:0;">
            @else
                <span style="display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;flex-shrink:0;
                             font-size:0.75rem;font-weight:600;
                             {{ $e['is_unknown'] ? 'background:#f6efd2;color:#b8860b;' : 'background:#eef1ff;color:#6e63e6;' }}">
                    {{ $initials($e['title']) }}
                </span>
            @endif
            <div style="min-width:0;flex:1;">
                <div style="font-size:0.875rem;font-weight:500;color:#1b1a2a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $e['title'] }}</div>
                <div style="font-size:0.75rem;color:#6b7088;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $e['location'] }} · {{ $e['time'] }}</div>
            </div>
            <span style="flex-shrink:0;padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;
                         {{ $e['is_unknown'] ? 'background:#f6efd2;color:#b8860b;' : 'background:#eaf7ee;color:#2f8f4e;' }}">
                {{ $e['status'] }}
            </span>
        </div>
    @empty
        <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;font-size:0.875rem;color:#6b7088;">
            Событий пока нет
        </div>
    @endforelse
    </div>
</div>
</div>
