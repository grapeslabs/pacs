@php /** @var array $tags */ @endphp

<div class="tags-cell">
    @foreach($tags as $t)
        <span class="tag-chip">{{ $t }}</span>
    @endforeach
</div>

@once
    <style>
        .tags-cell { display:flex; flex-wrap:wrap; gap:6px; max-width:260px; }
        .tag-chip { display:inline-flex; align-items:center; padding:4px 11px; border-radius:4px; background:#EAEDF8; color:#4E4E61; font-size:12px; font-weight:600; white-space:nowrap; }
    </style>
@endonce
