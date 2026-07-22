<div
    x-data="{
        open: false,
        modalId: 'detail-sidebar',
        stack: [],

        get canGoBack() { return this.stack.length > 0; },

        registerInStack() { Alpine.store('overlays')?.register(this.modalId, () => this.closeModal()); },
        unregisterFromStack() { Alpine.store('overlays')?.unregister(this.modalId); },

        toggleModal() {
            this.open = !this.open;
            if (this.open) { this.stack = []; this.registerInStack(); }
            else { this.unregisterFromStack(); }
            document.body.style.overflow = this.open ? 'hidden' : '';
        },

        closeModal() {
            this.open = false;
            this.stack = [];
            this.unregisterFromStack();
            document.body.style.overflow = '';
        },

        pushSnapshot() {
            const c = document.getElementById('detail-sidebar_content');
            if (c) { this.stack.push(c.innerHTML); }
        },

        back() {
            const html = this.stack.pop();
            if (html !== undefined) {
                const c = document.getElementById('detail-sidebar_content');
                if (c) { c.innerHTML = html; if (window.Alpine) { Alpine.initTree(c); } }
            }
        },
    }"
    @modal-toggled.window="
        let targetId = typeof $event.detail === 'object' ? $event.detail.id : $event.detail;
        if (targetId === modalId) { toggleModal(); }
    "
    @ds-push.window="pushSnapshot()"
    @toast.window="if (open && $event.detail.type === 'success') { closeModal(); }"
>
    <template x-teleport="body">
        <div x-show="open"
             style="display: none; position: fixed; inset: 0; z-index: 500; pointer-events: none;"
             role="dialog" aria-modal="true">

            <div x-show="open"
                 x-transition.opacity.duration.300ms
                 @click="closeModal()"
                 style="position: absolute; inset: 0; background-color: rgba(17, 24, 39, 0.6); pointer-events: auto;"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 style="position: absolute; top: 0; right: 0; bottom: 0; width: 33vw; min-width: 380px; max-width: 560px; background-color: #fff; box-shadow: -10px 0 25px -5px rgba(0,0,0,.15); pointer-events: auto;">

                <div id="detail-sidebar_content" style="display: flex; flex-direction: column; height: 100vh; max-height: 100vh; width: 100%;">
                    <div style="flex:1; display:flex; align-items:center; justify-content:center; color:#9CA3AF;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" style="animation: ds-spin 1s linear infinite;">
                            <circle cx="12" cy="12" r="9" stroke="#E5E7F5" stroke-width="3"/>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="#6972F0" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <style>
        @keyframes ds-spin { to { transform: rotate(360deg); } }
    </style>
</div>
