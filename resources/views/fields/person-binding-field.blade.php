<div class="pbind">
    <span class="pbind__ava">
        @if(!empty($photoUrl))
            <img src="{{ $photoUrl }}" alt="">
        @else
            <span class="pbind__ini">{{ $initials !== '' ? $initials : '?' }}</span>
        @endif
    </span>
    <div class="pbind__text">
        <div class="pbind__name">{{ $name }}</div>
        @if(!empty($subtitle))
            <div class="pbind__sub">{{ $subtitle }}</div>
        @endif
    </div>

    <style>
        .pbind { display:flex; align-items:center; gap:14px; background:#EEF0FE; border-radius:14px; padding:14px 16px; }
        .pbind__ava { flex:0 0 auto; width:46px; height:46px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#7E92F8,#6972F0); color:#fff; font-weight:700; font-size:16px; }
        .pbind__ava img { width:100%; height:100%; object-fit:cover; display:block; }
        .pbind__text { min-width:0; }
        .pbind__name { font-size:15px; font-weight:700; color:#22223A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .pbind__sub { font-size:12.5px; color:#8A8AA3; margin-top:2px; }
    </style>
</div>
