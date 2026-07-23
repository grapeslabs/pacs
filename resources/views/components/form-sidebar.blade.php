@props([
    'name' => 'form-sidebar',
    'title' => '',
    'subtitle' => '',
])

<div
    x-data="{
        open: false,
        isDirty: false,
        modalId: '{{ $name }}',
        modalTitle: '{{ is_callable($title) ? $title() : $title }}',
        modalSubtitle: '{{ is_callable($subtitle) ? $subtitle() : $subtitle }}',

        registerInStack() { Alpine.store('overlays')?.register(this.modalId, () => this.tryClose()); },
        unregisterFromStack() { Alpine.store('overlays')?.unregister(this.modalId); },

        toggleModal() {
            this.open = !this.open;
            this.open ? this.registerInStack() : this.unregisterFromStack();
            this.toggleBodyScroll();
        },

        tryClose() {
            if (this.isDirty) {
                window.dispatchEvent(new CustomEvent('open-unsaved-modal', { detail: { id: this.modalId } }));
            } else {
                this.closeModal();
            }
        },

        closeModal() {
            this.open = false;
            setTimeout(() => (this.isDirty = false), 300);
            this.unregisterFromStack();
            this.toggleBodyScroll();
        },

        forceClose() {
            this.isDirty = false;
            this.closeModal();
        },

        saveAndClose() {
            const form = document.querySelector(`#${this.modalId}_content form`);
            if (form) {
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        },

        toggleBodyScroll() {
            if (this.open) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },

        init() {
            const c = document.getElementById(this.modalId + '_content');
            if (c) {
                this.observer = new MutationObserver(() => this.syncTitleFromContent());
                this.observer.observe(c, { childList: true, subtree: true });
            }
        },

        syncTitleFromContent() {
            const c = document.getElementById(this.modalId + '_content');
            const el = c && c.querySelector('[data-sidebar-title]');
            if (el) {
                this.modalTitle = el.getAttribute('data-sidebar-title') || this.modalTitle;
                if (el.hasAttribute('data-sidebar-subtitle')) {
                    this.modalSubtitle = el.getAttribute('data-sidebar-subtitle');
                }
            }
        }
    }"
    @modal-toggled.window="
        let targetId = typeof $event.detail === 'object' ? $event.detail.id : $event.detail;
        if(targetId === modalId) {
            modalTitle = (typeof $event.detail === 'object' && $event.detail.title)
                ? $event.detail.title
                : '{{ is_callable($title) ? $title() : $title }}';
            modalSubtitle = (typeof $event.detail === 'object' && typeof $event.detail.subtitle !== 'undefined')
                ? $event.detail.subtitle
                : '{{ is_callable($subtitle) ? $subtitle() : $subtitle }}';
            toggleModal();
        }
    "
    @toast.window="if(open && (event.detail.type === 'success' || event.detail.type === 'warning')) { forceClose(); }"
    @unsaved-discard.window="if($event.detail && $event.detail.id === modalId) { forceClose(); }"
>
    <template x-teleport="body">

        <div x-show="open"
             style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 500; pointer-events: none;"
             role="dialog"
             aria-modal="true">

            <div x-show="open"
                 x-transition.opacity.duration.300ms
                 @click="tryClose()"
                 style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; background-color: rgba(17, 24, 39, 0.75); pointer-events: auto;">
            </div>

            <div x-show="open"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 style="position: absolute; top: 0; right: 0; bottom: 0; width: 32vw; min-width: 352px; background-color: #ffffff; box-shadow: -10px 0 25px -5px rgba(0, 0, 0, 0.1); pointer-events: auto;"
                 class="dark:bg-moonshine-dark">

                <div style="display: flex; flex-direction: column; height: 100vh; max-height: 100vh; width: 100%;">

                    <div style="display: flex; align-items: flex-start; justify-content: space-between; padding: 1.25rem 1.5rem; flex-shrink: 0; border-bottom: 1px solid #EAEDFB;">
                        <div style="min-width: 0;">
                            <h3 style="font-size: 19px; font-weight: 800; margin: 0; line-height: 1.4;" class="text-gray-900 dark:text-gray-100" x-text="modalTitle"></h3>
                            <p x-show="modalSubtitle" x-cloak style="font-size: 0.8125rem; color: #7F7F9D; margin: 0.125rem 0 0;" x-text="modalSubtitle"></p>
                        </div>
                        <button @click="tryClose()" type="button" style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; background: transparent; border: none; border-radius: 50%; cursor: pointer; padding: 0.25rem; transition: background-color .15s;" class="text-gray-400 hover:text-gray-500 hover:bg-gray-100" onmouseover="this.style.backgroundColor='#F1F3FB'" onmouseout="this.style.backgroundColor='transparent'">
                            <x-moonshine::icon icon="x-mark" size="6" />
                        </button>
                    </div>

                    <div style="flex: 1 1 0%; min-height: 0; overflow-y: auto; overscroll-behavior: contain; position: relative;">
                        <div id="{{ $name }}_content" style="padding: 1.5rem;" @input="isDirty = true" @change="isDirty = true">
                            {{ $slot ?? '' }}
                        </div>
                        <style>#{{ $name }}_content .js-form-sidebar-submit { display: none !important; }</style>
                    </div>

                    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid #EAEDFB;">
                        <button @click="tryClose()"
                                type="button"
                                style="padding: 0.625rem 1.5rem; border-radius: 9999px; background-color: #ffffff; color: #4E4E61; cursor: pointer; font-weight: 600; border: 1px solid #D7D0EF; font-size: 0.875rem;">
                            Отмена
                        </button>
                        <button @click="saveAndClose()"
                                type="button"
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.5rem; border-radius: 9999px; background-color: #6972F0; color: #ffffff; cursor: pointer; font-weight: 700; border: none; font-size: 0.875rem;">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.6673 5L7.50065 14.1667L3.33398 10" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Сохранить
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>
