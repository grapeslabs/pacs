@php
    /** @var string $title */
    /** @var string|null $subtitle */
@endphp

<div class="profile-page-header">
    <h1 class="profile-page-header__title">{{ $title }}</h1>
    @if(!empty($subtitle))
        <p class="profile-page-header__sub">{{ $subtitle }}</p>
    @endif
</div>

@once
    <style>
        .profile-page-header { max-width: 680px; margin: 4px 0 20px; }
        .profile-page-header__title { margin: 0; font-size: 26px; font-weight: 700; line-height: 1.15; color: #1a1a2e; }
        .profile-page-header__sub { margin: 6px 0 0; font-size: 14px; font-weight: 500; color: #8A8FA6; }

        /* Карточки профиля */
        .profile-card {
            max-width: 680px;
            margin-bottom: 20px;
            padding: 24px 26px !important;
            border: 1px solid #E6E9F5 !important;
            border-radius: 16px !important;
            background: #fff !important;
        }

        /* Заголовок секции внутри карточки */
        .profile-sec { margin-bottom: 18px; }
        .profile-sec__title {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6B7390;
        }
        .profile-sec__sub { margin: 12px 0 0; font-size: 13px; color: #8A8FA6; }
        .profile-sec__divider { margin-top: 12px; border: 0; border-top: 1px dashed #D9DEEE; }

        /* Лейблы чекбоксов внутри карточек — жирные */
        .profile-card label:has(input.form-checkbox),
        .profile-card label:has(> .form-checkbox),
        .profile-card .form-checkbox ~ span,
        .profile-card .form-checkbox-label { font-weight: 700 !important; }

        /* Кнопка формы — справа, как на макете */
        .profile-card .form > .grid:last-child .flex.w-full.flex-wrap { justify-content: flex-end; }

        /* Иконки на кнопках (галочка / щит) */
        .profile-submit { display: inline-flex; align-items: center; gap: 8px; }
        .profile-submit::before {
            content: "";
            width: 16px; height: 16px; flex: 0 0 auto;
            background-color: currentColor;
            -webkit-mask: var(--profile-submit-ico) no-repeat center / contain;
            mask: var(--profile-submit-ico) no-repeat center / contain;
        }
        .profile-submit--check {
            --profile-submit-ico: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 6 9 17l-5-5'/%3E%3C/svg%3E");
        }
        .profile-submit--shield {
            --profile-submit-ico: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 3 5 6v6c0 4 3 7 7 9 4-2 7-5 7-9V6z'/%3E%3Cpath d='M9 12l2 2 4-4'/%3E%3C/svg%3E");
        }
    </style>
@endonce
