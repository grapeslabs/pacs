<div {{ $attributes->class(['box']) }}>
    <div class="box-body">
        <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column;">
            @forelse($items as $item)
                <li style="display: flex; align-items: flex-start; gap: 1rem;
                           padding-top: {{ $loop->first ? '0' : '1rem' }};
                           padding-bottom: {{ $loop->last ? '0' : '1rem' }};
                           {{ !$loop->last ? 'border-bottom: 1px dashed #cbd5e1;' : '' }}">
                    <div style="flex-shrink: 0; margin-top: 0.125rem;">
                        <img src="{{ $item['icon'] }}" alt="icon" style="width: 32px; height: 32px; object-fit: contain;">
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.875rem; font-weight: 500;">
                            {{ $item['title'] ?? 'Неизвестное событие' }}
                        </span>
                        <span style="font-size: 0.875rem; margin-top: 0.125rem; color: {{ !empty($item['isError']) ? '#ef4444' : '#64748b' }};">
                            {{ $item['subtitle'] ?? '' }}
                        </span>

                    </div>
                </li>
            @empty
                <li style="padding: 1rem 0; text-align: center; font-size: 0.875rem; color: #94a3b8;">
                    Событий пока нет
                </li>
            @endforelse
        </ul>
    </div>
</div>
