<style>
    .ucm_wrapper {
        position: relative;
        z-index: 10000;
    }

    .ucm_backdrop {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background-color: rgba(15, 23, 42, 0.4);
        transition-property: opacity;
    }

    .ucm_scroll-area {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 10000;
        overflow-y: auto;
    }

    .ucm_flex-center {
        display: flex;
        min-height: 100%;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        text-align: center;
    }

    @media (min-width: 640px) {
        .ucm_flex-center {
            padding: 0;
        }
    }

    .ucm_modal-panel {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background-color: #ffffff;
        text-align: left;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        width: 100%;
        max-width: 520px;
        padding: 24px;
    }

    .ucm_close-wrapper {
        position: absolute;
        right: 20px;
        top: 20px;
    }

    .ucm_close-btn {
        color: #9ca3af;
        background: transparent;
        border: none;
        padding: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ucm_close-btn:hover {
        color: #4b5563;
    }

    .ucm_close-btn:focus {
        outline: none;
    }

    .ucm_close-icon {
        height: 20px;
        width: 20px;
    }

    .ucm_title {
        font-size: 19px;
        font-weight: 700;
        color: #111827;
        margin: 0;
        padding-right: 24px;
    }

    .ucm_text-wrapper {
        margin-top: 8px;
    }

    .ucm_text {
        font-size: 15px;
        color: #4b5563;
        margin: 0;
        line-height: 1.4;
    }

    .ucm_actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
    }

    .ucm_btn {
        flex: 1 1 0%;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 9999px;
        padding: 11px 16px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition-property: background-color, opacity;
        transition-duration: 150ms;
    }

    .ucm_btn:focus {
        outline: none;
    }

    .ucm_btn-cancel {
        background-color: #F1F3FB;
        color: #4E4E61;
    }

    .ucm_btn-cancel:hover {
        background-color: #E7EAF6;
    }

    .ucm_btn-discard {
        background-color: #6972F0;
        color: #ffffff;
    }

    .ucm_btn-discard:hover {
        background-color: #5A63E0;
    }

    .ucm_ease-out {
        transition-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }

    .ucm_ease-in {
        transition-timing-function: cubic-bezier(0.4, 0, 1, 1);
    }

    .ucm_duration-300 {
        transition-duration: 300ms;
    }

    .ucm_duration-200 {
        transition-duration: 200ms;
    }

    .ucm_opacity-0 {
        opacity: 0;
    }

    .ucm_opacity-100 {
        opacity: 1;
    }

    .ucm_modal-start {
        opacity: 0;
        transform: translateY(1rem);
    }

    .ucm_modal-end {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    @media (min-width: 640px) {
        .ucm_modal-start {
            transform: translateY(0) scale(0.95);
        }
    }
</style>

<div x-data="{
        isOpen: false,
        targetId: '',
        open(event) {
            this.targetId = (event.detail && event.detail.id) ? event.detail.id : '';
            this.isOpen = true;
        },
        close() { this.isOpen = false; },
        discard() {
            this.isOpen = false;
            window.dispatchEvent(new CustomEvent('unsaved-discard', { detail: { id: this.targetId } }));
        }
    }"
     @open-unsaved-modal.window="open($event)"
     @keydown.escape.window="close()"
>
    <div x-show="isOpen" class="ucm_wrapper" x-cloak role="dialog" aria-modal="true">
        <div x-show="isOpen"
             x-transition:enter="ucm_ease-out ucm_duration-300"
             x-transition:enter-start="ucm_opacity-0"
             x-transition:enter-end="ucm_opacity-100"
             x-transition:leave="ucm_ease-in ucm_duration-200"
             x-transition:leave-start="ucm_opacity-100"
             x-transition:leave-end="ucm_opacity-0"
             class="ucm_backdrop"></div>

        <div class="ucm_scroll-area">
            <div class="ucm_flex-center" @click.self="close()">
                <div x-show="isOpen"
                     x-transition:enter="ucm_ease-out ucm_duration-300"
                     x-transition:enter-start="ucm_modal-start"
                     x-transition:enter-end="ucm_modal-end"
                     x-transition:leave="ucm_ease-in ucm_duration-200"
                     x-transition:leave-start="ucm_modal-end"
                     x-transition:leave-end="ucm_modal-start"
                     class="ucm_modal-panel">

                    <div class="ucm_close-wrapper">
                        <button @click="close()" type="button" class="ucm_close-btn">
                            <svg class="ucm_close-icon" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <h3 class="ucm_title">Несохранённые изменения</h3>
                    <div class="ucm_text-wrapper">
                        <p class="ucm_text">Вы внесли изменения, которые ещё не сохранены. Если выйдете, все изменения
                            будут потеряны.</p>
                    </div>

                    <div class="ucm_actions">
                        <button type="button" @click="close()" class="ucm_btn ucm_btn-cancel">
                            Отмена
                        </button>
                        <button type="button" @click="discard()" class="ucm_btn ucm_btn-discard">
                            Закрыть без сохранения
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
