<style>
    [x-cloak] {
        display: none !important;
    }

    .ccm_wrapper {
        position: relative;
        z-index: 9999;
    }

    .ccm_backdrop {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background-color: rgba(15, 23, 42, 0.4);
        transition-property: opacity;
    }

    .ccm_scroll-area {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 9999;
        overflow-y: auto;
    }

    .ccm_flex-center {
        display: flex;
        min-height: 100%;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        text-align: center;
    }

    @media (min-width: 640px) {
        .ccm_flex-center {
            padding: 0;
        }
    }

    .ccm_modal-panel {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background-color: #ffffff;
        text-align: left;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transition-property: all;
        width: 100%;
        max-width: 410px;
        padding: 24px;
    }

    .ccm_close-wrapper {
        position: absolute;
        right: 20px;
        top: 20px;
    }

    .ccm_close-btn {
        color: #9ca3af;
        background: transparent;
        border: none;
        padding: 4px;
        cursor: pointer;
        transition-property: color;
        transition-duration: 150ms;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ccm_close-btn:hover {
        color: #4b5563;
    }

    .ccm_close-btn:focus {
        outline: none;
    }

    .ccm_close-btn:disabled {
        opacity: 0.5;
        cursor: default;
    }

    .ccm_close-icon {
        height: 20px;
        width: 20px;
    }

    .ccm_title {
        font-size: 19px;
        font-weight: 700;
        color: #111827;
        margin: 0;
        padding-right: 24px
    }

    .ccm_text-wrapper {
        margin-top: 8px;
    }

    .ccm_text {
        font-size: 15px;
        color: #4b5563;
        margin: 0;
    }

    .ccm_actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
    }

    .ccm_btn {
        flex: 1 1 0%;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 12px;
        padding: 11px 16px;
        font-size: 15px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition-property: background-color, opacity;
        transition-duration: 150ms;
    }

    .ccm_btn:focus {
        outline: none;
    }

    .ccm_btn-cancel {
        background-color: #EEF2FF;
        color: #6366F1;
    }

    .ccm_btn-cancel:hover:not(:disabled) {
        background-color: #E0E7FF;
    }

    .ccm_btn-cancel:disabled {
        opacity: 0.7;
    }

    .ccm_btn-delete {
        background-color: #F04138;
        color: #ffffff;
    }

    .ccm_btn-delete:hover:not(:disabled) {
        background-color: #E11D48;
    }

    .ccm_btn-delete:disabled {
        opacity: 0.8;
        cursor: wait;
    }

    @keyframes ccm_spin {
        to {
            transform: rotate(360deg);
        }
    }

    .ccm_spinner {
        animation: ccm_spin 1s linear infinite;
        margin-left: -0.25rem;
        margin-right: 0.5rem;
        height: 1rem;
        width: 1rem;
        color: #ffffff;
    }

    .ccm_spinner-circle {
        opacity: 0.25;
    }

    .ccm_spinner-path {
        opacity: 0.75;
    }

    .ccm_ease-out {
        transition-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }

    .ccm_ease-in {
        transition-timing-function: cubic-bezier(0.4, 0, 1, 1);
    }

    .ccm_duration-300 {
        transition-duration: 300ms;
    }

    .ccm_duration-200 {
        transition-duration: 200ms;
    }

    .ccm_opacity-0 {
        opacity: 0;
    }

    .ccm_opacity-100 {
        opacity: 1;
    }

    .ccm_modal-start {
        opacity: 0;
        transform: translateY(1rem);
    }

    .ccm_modal-end {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    @media (min-width: 640px) {
        .ccm_modal-start {
            transform: translateY(0) scale(0.95);
        }
    }
</style>

<div x-data="{
        isOpen: false,
        isLoading: false,
        formAction: '',
        open(event) {
            this.formAction = event.detail.url;
            this.isOpen = true;
            this.isLoading = false;
        },
        close() {
            if (!this.isLoading) {
                this.isOpen = false;
            }
        }
    }"
     @open-custom-delete-modal.window="open($event)"
     @keydown.escape.window="close()"
>
    <div x-show="isOpen" class="ccm_wrapper" x-cloak aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isOpen"
             x-transition:enter="ccm_ease-out ccm_duration-300"
             x-transition:enter-start="ccm_opacity-0"
             x-transition:enter-end="ccm_opacity-100"
             x-transition:leave="ccm_ease-in ccm_duration-200"
             x-transition:leave-start="ccm_opacity-100"
             x-transition:leave-end="ccm_opacity-0"
             class="ccm_backdrop"></div>

        <div class="ccm_scroll-area">
            <div class="ccm_flex-center" @click.self="close()">
                <!-- Панель модального окна -->
                <div x-show="isOpen"
                     x-transition:enter="ccm_ease-out ccm_duration-300"
                     x-transition:enter-start="ccm_modal-start"
                     x-transition:enter-end="ccm_modal-end"
                     x-transition:leave="ccm_ease-in ccm_duration-200"
                     x-transition:leave-start="ccm_modal-end"
                     x-transition:leave-end="ccm_modal-start"
                     class="ccm_modal-panel">

                    <div class="ccm_close-wrapper">
                        <button @click="close()" :disabled="isLoading" type="button" class="ccm_close-btn">
                            <svg class="ccm_close-icon" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div>
                        <h3 class="ccm_title" id="modal-title">
                            Подтвердите действие
                        </h3>
                        <div class="ccm_text-wrapper">
                            <p class="ccm_text">
                                Вы уверены, что хотите удалить запись?
                            </p>
                        </div>
                    </div>

                    <form :action="formAction" method="POST" @submit="isLoading = true">
                        @csrf
                        @method('DELETE')
                        <div class="ccm_actions">
                            <button type="button"
                                    @click="close()"
                                    :disabled="isLoading"
                                    class="ccm_btn ccm_btn-cancel">
                                Отмена
                            </button>
                            <button type="submit"
                                    :disabled="isLoading"
                                    class="ccm_btn ccm_btn-delete">
                                <svg x-show="isLoading" class="ccm_spinner" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24" style="display: none;">
                                    <circle class="ccm_spinner-circle" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                    <path class="ccm_spinner-path" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isLoading ? 'Удаление...' : 'Удалить'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
