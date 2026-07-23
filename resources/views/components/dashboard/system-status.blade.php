@php
    $tb = function ($bytes) {
        if (!$bytes) return '0 ТБ';
        $t = $bytes / (1024 ** 4);
        return $t >= 1 ? number_format($t, 1, '.', ' ') . ' ТБ' : round($bytes / (1024 ** 3)) . ' ГБ';
    };
    $pct = $system['storage']['pct'] ?? 0;
    if ($pct < 50) {
        $barColor = '#2f8f4e'; $iconBg = '#eaf7ee'; $iconColor = '#2f8f4e'; $blockBg = '#f4faf6';
    } elseif ($pct < 90) {
        $barColor = '#f59e0b'; $iconBg = '#fbeec2'; $iconColor = '#b8860b'; $blockBg = '#fdfaf0';
    } else {
        $barColor = '#f04138'; $iconBg = '#fbf0ef'; $iconColor = '#f04138'; $blockBg = '#fdf5f4';
    }
    $overall = $pct < 90 ? ['Норма', 'background:#eaf7ee;color:#2f8f4e;'] : ['Внимание', 'background:#f6efd2;color:#b8860b;'];
    $aiWorking = $system['ai']['working'] ?? false;
    $notResp = $system['cameras']['not_responding'] ?? 0;
@endphp
<div {{ $attributes }}
     style="display:flex;flex-direction:column;height:350px;box-sizing:border-box;border:1px solid #e3e7f5;background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(27,26,42,0.05);overflow:hidden;">
    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e3e7f5;">
        <h2 style="margin:0;font-size:1rem;font-weight:600;color:#1b1a2a;">Состояние системы</h2>
        <span style="padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;{{ $overall[1] }}">{{ $overall[0] }}</span>
    </div>
    <div style="flex:1;min-height:0;overflow-y:auto;">

    <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid #eef1f7;">
        <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;flex-shrink:0;background:#faf9f5;color:#6e63e6;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16v10H4z"></path><path d="M8 21h8"></path><path d="M12 17v4"></path><path d="M8 11h.01"></path><path d="M11 11h.01"></path></svg>        </span>
        <div style="min-width:0;flex:1;">
            <div style="font-size:0.875rem;font-weight:500;color:#1b1a2a;">Оборудование онлайн</div>
            <div style="font-size:0.75rem;color:#6b7088;">Терминалы и контроллеры</div>
        </div>
        <span style="flex-shrink:0;font-size:0.875rem;font-weight:600;color:#1b1a2a;font-variant-numeric:tabular-nums;">
            {{ $system['equipment']['online'] }} / {{ $system['equipment']['total'] }}
        </span>
    </div>

    <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid #eef1f7;">
        <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;flex-shrink:0;background:#faf9f5;color:#6e63e6;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v6c0 4 3 7 7 9 4-2 7-5 7-9V6z"></path><path d="M9 12l2 2 4-4"></path></svg>        </span>
        <div style="min-width:0;flex:1;">
            <div style="font-size:0.875rem;font-weight:500;color:#1b1a2a;">AI-аналитика</div>
            <div style="font-size:0.75rem;color:#6b7088;">Включена на {{ $system['ai']['cameras_on'] }} из {{ $system['ai']['cameras_total'] }} камер</div>
        </div>
        <span style="flex-shrink:0;padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;{{ $aiWorking ? 'background:#eaf7ee;color:#2f8f4e;' : 'background:#faf9f5;color:#6b7088;' }}">
            {{ $aiWorking ? 'Работает' : 'Отключена' }}
        </span>
    </div>

    <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid #eef1f7;">
        <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;flex-shrink:0;background:#faf9f5;color:#6e63e6;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1.5 1.5"></path><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1.5-1.5"></path></svg>
        </span>
        <div style="min-width:0;flex:1;">
            <div style="font-size:0.875rem;font-weight:500;color:#1b1a2a;">Связь с камерами</div>
            <div style="font-size:0.75rem;color:#6b7088;">{{ $system['cameras']['online'] }} онлайн · {{ $notResp }} не отвечают</div>
        </div>
        <span style="flex-shrink:0;padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;{{ $notResp === 0 ? 'background:#eaf7ee;color:#2f8f4e;' : 'background:#f6efd2;color:#b8860b;' }}">
            {{ $notResp === 0 ? 'Стабильно' : 'Сбои' }}
        </span>
    </div>

    <div style="padding:14px 20px;background:{{ $blockBg }};">
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;flex-shrink:0;background:{{ $iconBg }};color:{{ $iconColor }};">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v4H4z"></path><path d="M4 10h16v4H4z"></path><path d="M4 15h16v4H4z"></path><path d="M7 7h.01"></path><path d="M7 12h.01"></path><path d="M7 17h.01"></path></svg>            </span>
            <div style="min-width:0;flex:1;">
                <div style="font-size:0.875rem;font-weight:500;color:#1b1a2a;">Хранилище архива</div>
                <div style="font-size:0.75rem;color:#6b7088;">{{ $tb($system['storage']['used']) }} из {{ $tb($system['storage']['total']) }}</div>
            </div>
            <span style="flex-shrink:0;font-size:0.875rem;font-weight:600;color:#1b1a2a;font-variant-numeric:tabular-nums;">{{ $pct }}%</span>
        </div>
        <div style="margin-top:10px;height:8px;width:100%;border-radius:9999px;background:#e3e7f5;overflow:hidden;">
            <div style="height:100%;border-radius:9999px;width:{{ $pct }}%;background:{{ $barColor }};"></div>
        </div>
    </div>
    </div>
</div>
