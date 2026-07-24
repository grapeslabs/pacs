@php
    /** @var string $avatarUrl */
    /** @var bool $hasAvatar */
    /** @var string $uploadUrl */
    /** @var string $deleteUrl */
@endphp

<div class="profile-avatar" x-data>
    <img class="profile-avatar__img" src="{{ $avatarUrl }}" alt="Аватар">

    <button type="button" class="profile-avatar__upload" @click="$refs.avatarFile.click()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
        </svg>
        Изменить фото
    </button>

    @if($hasAvatar)
        <form action="{{ $deleteUrl }}" method="POST" class="profile-avatar__delete-form">
            @csrf
            <button type="submit" class="profile-avatar__delete" title="Удалить фото" aria-label="Удалить фото">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>
                </svg>
            </button>
        </form>
    @endif

    {{-- Выбор файла сразу отправляет форму, без модалок --}}
    <form x-ref="avatarForm" action="{{ $uploadUrl }}" method="POST" enctype="multipart/form-data" style="display:none;">
        @csrf
        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif" x-ref="avatarFile"
               @change="$refs.avatarFile.files.length && $refs.avatarForm.submit()">
    </form>
</div>

@once
    <style>
        .profile-avatar { display: flex; align-items: center; gap: 18px; }
        .profile-avatar__img {
            width: 84px; height: 84px; border-radius: 50%; object-fit: cover;
            flex: 0 0 auto; background: #F1F3FB;
        }
        .profile-avatar__upload {
            display: inline-flex; align-items: center; gap: 8px;
            height: 44px; padding: 0 20px;
            background: #fff; border: 1px solid #E0E4F0; border-radius: 999px;
            font-family: inherit; font-size: 14px; font-weight: 600; color: #2C3142;
            cursor: pointer; transition: background-color .15s ease, border-color .15s ease;
        }
        .profile-avatar__upload:hover { background: #F4F6FC; border-color: #C8D0EB; }
        .profile-avatar__upload svg { color: #6B7390; }
        .profile-avatar__delete-form { display: inline-flex; }
        .profile-avatar__delete {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border: none; border-radius: 10px;
            background: transparent; color: #8A90A6; cursor: pointer;
            transition: background-color .15s ease, color .15s ease;
        }
        .profile-avatar__delete:hover { background: #FBECEC; color: #F04138; }
    </style>
@endonce
