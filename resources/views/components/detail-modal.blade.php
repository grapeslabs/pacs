<div
    x-data="{
        open: false,
        modalId: 'detail-modal',

        registerInStack() { Alpine.store('overlays')?.register(this.modalId, () => this.closeModal()); },
        unregisterFromStack() { Alpine.store('overlays')?.unregister(this.modalId); },

        toggleModal() {
            this.open = !this.open;
            if (this.open) { this.registerInStack(); }
            else { this.unregisterFromStack(); this.clearRowMarks(); }
            document.body.style.overflow = this.open ? 'hidden' : '';
        },

        clearRowMarks() {
            document.querySelectorAll('tr.ds-row-active').forEach(el => el.classList.remove('ds-row-active'));
        },

        closeModal() {
            this.open = false;
            this.unregisterFromStack();
            this.clearRowMarks();
            document.body.style.overflow = '';
        },
    }"
    @modal-toggled.window="
        let targetId = typeof $event.detail === 'object' ? $event.detail.id : $event.detail;
        if (targetId === modalId) { toggleModal(); }
    "
    @keydown.escape.window="if (open) closeModal()"
    @toast.window="if (open && $event.detail.type === 'success') { closeModal(); }"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="dm-overlay" role="dialog" aria-modal="true">

            <div x-show="open"
                 x-transition.opacity.duration.250ms
                 @click="closeModal()"
                 style="position: absolute; inset: 0; background-color: rgba(17, 24, 39, 0.6); pointer-events: auto;"></div>

            <div x-show="open"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 style="position: relative; pointer-events: auto; max-width: 100%; max-height: calc(100vh - 48px); overflow: auto;">

                <div id="detail-modal_content">
                    <div style="width: 360px; max-width: 100%; background:#fff; border-radius:18px; padding:48px; display:flex; align-items:center; justify-content:center; color:#9CA3AF;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" style="animation: dm-spin 1s linear infinite;">
                            <circle cx="12" cy="12" r="9" stroke="#E5E7F5" stroke-width="3"/>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="#6972F0" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <style>
        @keyframes dm-spin { to { transform: rotate(360deg); } }
        [x-cloak] { display: none !important; }
        .dm-overlay {
            position: fixed; inset: 0; z-index: 500;
            display: flex; align-items: center; justify-content: center;
            padding: 24px; pointer-events: none;
        }
    </style>
</div>
