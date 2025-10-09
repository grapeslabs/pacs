<style>
    .ms-warning-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        background-color: #fffafb;
        border: 2px dashed #ef4444;
        border-radius: 12px;
        margin-bottom: 24px;
        font-family: inherit;
        width: 45%;
    }
    .ms-warning-icon {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        color: #ef4444;
        margin-top: 2px;
    }
    .ms-warning-text {
        margin: 0;
        color: #374151;
        font-size: 14px;
        line-height: 1.5;
    }
    .ms-warning-highlight {
        color: #ef4444;
        font-weight: 500;
    }
</style>

<div class="ms-warning-alert">
    <div class="ms-warning-icon">
        <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" style="display: none;"></path>
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
    </div>

    <p class="ms-warning-text">
        Убедитесь, что ресурсов текущего сервера достаточно для работы функции.<br>
        В противном случае высока вероятность сбоев и отказа сервера.<br>
        Для бесперебойной работы программы необходимо <span class="ms-warning-highlight">минимум 4 ядра CPU.</span>
    </p>
</div>
