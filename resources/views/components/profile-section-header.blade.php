@php
    /** @var string $title */
    /** @var string|null $subtitle */
@endphp

<div class="profile-sec">
    <h3 class="profile-sec__title">{{ $title }}</h3>
    @if(!empty($subtitle))
        <p class="profile-sec__sub">{{ $subtitle }}</p>
    @endif
    <hr class="profile-sec__divider">
</div>
