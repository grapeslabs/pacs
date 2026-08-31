@php
    /**
     * Payload-driven рендерер модального окна детального просмотра записи отчёта.
     *
     * Схема $data:
     *   'title'   => string                      — заголовок модалки
     *   'layout'  => 'portrait' | 'gallery'      — раскладка (по умолчанию portrait)
     *   // portrait: одно фото-портрет слева + строки справа
     *   'photo'    => ?string (url)              — фото; если null → плейсхолдер
     *   'initials' => ?string                    — инициалы в плейсхолдере
     *   // gallery: подписанные фото сверху + строки на всю ширину снизу
     *   'gallery' => [ ['label'=>string, 'image'=>?url, 'plate'=>?string], ... ]
     *   'rows'    => [ ['k'=>string, 'v'=>?string, 'mono'=>bool,
     *                   'badge'=>['label'=>string,'tone'=>string], 'link'=>?url], ... ]
     *   'footer'  => ?['label'=>string, 'href'=>string, 'icon'=>?svg,
     *                  'async'=>?selector, 'dispatch'=>?js]
     */
    $data     = $data ?? [];
    $layout   = $data['layout'] ?? 'portrait';
    $rows     = $data['rows'] ?? [];
    $footer   = $data['footer'] ?? null;
    $gallery  = $data['gallery'] ?? [];

    // Палитра бейджей = пресеты App\MoonShine\Fields\ColoredSelectField (единый вид с таблицами).
    $toneStyles = [
        'green'  => ['bg' => '#C9F3CE', 'text' => '#36944C', 'dot' => '#51D16D'],
        'yellow' => ['bg' => '#fef9c3', 'text' => '#854d0e', 'dot' => '#eab308'],
        'orange' => ['bg' => '#ffedd5', 'text' => '#7c2d12', 'dot' => '#f97316'],
        'pink'   => ['bg' => '#ffe4e6', 'text' => '#9f1239', 'dot' => '#f43f5e'],
        'red'    => ['bg' => '#FBF5F5', 'text' => '#C2322B', 'dot' => '#F04138'],
        'blue'   => ['bg' => '#EFF1FF', 'text' => '#7381F4', 'dot' => '#7381F4'],
        'purple' => ['bg' => '#f3e8ff', 'text' => '#6b21a8', 'dot' => '#a855f7'],
        'gray'   => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#6b7280'],
    ];

    $renderValue = function (array $row) use ($toneStyles) {
        if (! empty($row['badge'])) {
            $t = $toneStyles[$row['badge']['tone'] ?? 'gray'] ?? $toneStyles['gray'];
            return '<span class="dm-badge" style="background:' . $t['bg'] . ';color:' . $t['text'] . ';">'
                . '<span class="dm-badge__dot" style="background:' . $t['dot'] . ';"></span>'
                . e($row['badge']['label'] ?? '') . '</span>';
        }

        $text = $row['v'] ?? '—';
        if ($text === null || $text === '') {
            $text = '—';
        }

        $classes = 'dm-v';
        if (! empty($row['mono'])) {
            $classes .= ' dm-mono';
        }

        if (! empty($row['link'])) {
            return '<a href="' . e($row['link']) . '" class="' . $classes . ' dm-link">' . e($text) . '</a>';
        }

        return '<span class="' . $classes . '">' . e($text) . '</span>';
    };
@endphp

<div class="dm-modal {{ $layout === 'gallery' ? 'dm-modal--wide' : '' }}">
    <button type="button" class="dm-close"
            @click="$dispatch('modal-toggled', { id: 'detail-modal' })" aria-label="Закрыть">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </button>

    <h2 class="dm-title">{{ $data['title'] ?? 'Запись' }}</h2>

    @if($layout === 'gallery')
        <div class="dm-gallery">
            @foreach($gallery as $g)
                <div class="dm-gallery__item">
                    <div class="dm-gallery__label">{{ $g['label'] ?? '' }}</div>
                    @if(!empty($g['image']))
                        <img src="{{ $g['image'] }}" alt="" class="dm-gallery__img"
                             onerror="this.parentElement.querySelector('.dm-ph')?.style.setProperty('display','flex'); this.style.display='none';">
                        <div class="dm-photo-ph dm-ph" style="display:none;"><span>Нет фото</span></div>
                    @elseif(!empty($g['plate']))
                        <div class="dm-gallery__plate">{!! \App\MoonShine\Fields\LicensePlateField::previewHtml($g['plate']) !!}</div>
                    @else
                        <div class="dm-photo-ph"><span>Нет фото</span></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="dm-kv">
            @foreach($rows as $row)
                <div class="dm-row"><span class="dm-k">{{ $row['k'] ?? '' }}</span>{!! $renderValue($row) !!}</div>
            @endforeach
        </div>
    @else
        <div class="dm-portrait">
            <div class="dm-photo">
                @if(!empty($data['photo']))
                    <img src="{{ $data['photo'] }}" alt="" class="dm-photo__img"
                         onerror="this.parentElement.querySelector('.dm-photo__fallback').style.display='flex'; this.style.display='none';">
                    <div class="dm-photo__fallback" style="display:none;">
                        <span>{{ $data['initials'] ?? '' }}</span>
                    </div>
                @elseif(!empty($data['initials']))
                    <div class="dm-photo__fallback"><span>{{ $data['initials'] }}</span></div>
                @else
                    <div class="dm-photo__fallback">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    </div>
                @endif
            </div>

            <div class="dm-kv">
                @foreach($rows as $row)
                    <div class="dm-row"><span class="dm-k">{{ $row['k'] ?? '' }}</span>{!! $renderValue($row) !!}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if($footer)
        <div class="dm-actions">
            @if(!empty($footer['html']))
                {!! $footer['html'] !!}
            @else
                <a href="{{ $footer['href'] ?? '#' }}" class="dm-btn dm-btn--primary"
                   @if(!empty($footer['dispatch'])) @click.prevent="{{ $footer['dispatch'] }}" @endif>
                    @if(!empty($footer['icon'])){!! $footer['icon'] !!}@endif
                    <span>{{ $footer['label'] ?? '' }}</span>
                </a>
            @endif
        </div>
    @endif
</div>

@once
<style>
    .dm-modal {
        position: relative;
        width: 780px;
        max-width: 100%;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 20px 80px rgba(127,127,157,.18);
        padding: 22px;
    }
    .dm-modal--wide { width: 765px; }

    .dm-close {
        position: absolute; top: 14px; right: 14px;
        width: 32px; height: 32px; border-radius: 50%;
        border: 0; background: transparent; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: #66667D; transition: background .15s;
    }
    .dm-close:hover { background: #EAEDF8; }

    .dm-title { margin: 0 0 16px; font-weight: 800; font-size: 18px; color: #1E1E1E; padding-right: 32px; }

    .dm-portrait { display: grid; grid-template-columns: 190px 1fr; gap: 24px; align-items: start; }

    .dm-photo {
        width: 190px; height: 210px; border-radius: 14px; overflow: hidden;
        background: #EAEDF8; display: flex; align-items: center; justify-content: center;
    }
    .dm-photo__img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .dm-photo__fallback {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        color: #AAB4C9; font-weight: 800; font-size: 40px; letter-spacing: 1px;
    }

    .dm-gallery { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .dm-gallery__label { font-weight: 700; font-size: 12px; color: #7F7F9D; margin-bottom: 6px; }
    .dm-gallery__img { width: 100%; height: 180px; object-fit: contain; border-radius: 12px; background: #EAEDF8; display: block; }
    .dm-gallery__plate {
        width: 100%; height: 180px; border-radius: 12px; background: #EAEDF8;
        display: flex; align-items: center; justify-content: center;
    }
    .dm-gallery__plate .plate { transform: scale(1.4); }
    .dm-photo-ph {
        width: 100%; height: 180px; border-radius: 12px; background: #EAEDF8;
        display: flex; align-items: center; justify-content: center;
        color: #AAB4C9; font-weight: 600; font-size: 13px;
    }

    .dm-kv { display: flex; flex-direction: column; }
    .dm-row {
        display: flex; justify-content: space-between; gap: 16px; align-items: center;
        padding: 11px 0; border-bottom: 1px solid #EAEDF8; font-size: 14px;
    }
    .dm-row:last-child { border-bottom: 0; }
    .dm-k { color: #66667D; font-weight: 600; flex: 0 0 auto; }
    .dm-v { color: #1E1E1E; font-weight: 700; text-align: right; word-break: break-word; }
    .dm-mono { font-family: 'SFMono-Regular', ui-monospace, Menlo, Consolas, monospace; }
    .dm-link { color: #7381F4; text-decoration: none; cursor: pointer; }
    .dm-link:hover { text-decoration: underline; }

    .dm-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 20px;
        font-size: 13px; font-weight: 600; white-space: nowrap;
    }
    .dm-badge__dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

    .dm-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .dm-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 12px; border: 0; cursor: pointer;
        font-weight: 700; font-size: 14px; text-decoration: none;
    }
    .dm-btn svg { width: 18px; height: 18px; }
    .dm-btn--primary { background: linear-gradient(180deg,#7E92F8,#6972F0); color: #fff; }
    .dm-btn--primary:hover { filter: brightness(1.03); }
</style>
@endonce
