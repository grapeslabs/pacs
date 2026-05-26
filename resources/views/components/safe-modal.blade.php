@props([
    'name' => 'safe-modal',
    'title' => '',
])

<div
    x-data="{
        open: false,
        isDirty: false,
        showConfirm: false,
        modalId: '{{ $name }}',
        modalTitle: '{{ is_callable($title) ? $title() : $title }}',

        registerInStack() { Alpine.store('overlays')?.register(this.modalId, () => this.tryClose()); },
        unregisterFromStack() { Alpine.store('overlays')?.unregister(this.modalId); },

        toggleModal() {
            this.open = !this.open;
            this.open ? this.registerInStack() : this.unregisterFromStack();
            this.toggleBodyScroll();
        },

        tryClose() { this.isDirty ? (this.showConfirm = true) : this.closeModal(); },

        closeModal() {
            this.open = false;
            this.showConfirm = false;
            setTimeout(() => (this.isDirty = false), 300);
            this.unregisterFromStack();
            this.toggleBodyScroll();
        },

        forceClose() {
            this.isDirty = false;
            this.showConfirm = false;
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
        }
    }"
    @modal-toggled.window="
        let targetId = typeof $event.detail === 'object' ? $event.detail.id : $event.detail;
        if(targetId === modalId) {
            modalTitle = (typeof $event.detail === 'object' && $event.detail.title)
                ? $event.detail.title
                : '{{ is_callable($title) ? $title() : $title }}';
            toggleModal();
        }
    "
    @toast.window="if(open && event.detail.type === 'success') { forceClose(); }"
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
                 style="position: absolute; top: 0; right: 0; bottom: 0; width: 45vw; min-width: 320px; background-color: #ffffff; box-shadow: -10px 0 25px -5px rgba(0, 0, 0, 0.1); pointer-events: auto;"
                 class="dark:bg-moonshine-dark">

                <div style="display: flex; flex-direction: column; height: 100vh; max-height: 100vh; width: 100%;">

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;" class="dark:border-moonshine-dark-500">
                        <h3 style="font-size: 1.125rem; font-weight: 500; margin: 0;" class="text-gray-900 dark:text-gray-100" x-text="modalTitle"></h3>
                        <button @click="tryClose()" type="button" style="background: transparent; border: none; cursor: pointer; padding: 0.25rem;" class="text-gray-400 hover:text-gray-500">
                            <x-moonshine::icon icon="x-mark" size="6" />
                        </button>
                    </div>

                    <div style="flex: 1 1 0%; min-height: 0; overflow-y: auto; overscroll-behavior: contain; position: relative;">

                        <div id="{{ $name }}_content" style="padding: 1.5rem;" @input="isDirty = true" @change="isDirty = true">
                            {{ $slot ?? '' }}
                        </div>

                    </div>

                </div>

                <div x-show="showConfirm"
                     x-transition.opacity.duration.200ms
                     style="display: none; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 50;">

                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: rgba(17, 24, 39, 0.75);" class="dark:bg-moonshine-dark/75">
                        <div style="background-color: #ffffff; border-radius: 16px; padding: 24px; width: 90%; max-width: 340px; position: relative; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" class="dark:bg-moonshine-dark">

                            <button @click="showConfirm = false" type="button" style="position: absolute; top: 12px; right: 12px; background: transparent; border: none; cursor: pointer; padding: 0.25rem;" class="text-gray-400 hover:text-gray-500">
                                <x-moonshine::icon icon="x-mark" size="5" />
                            </button>

                            <h3 style="font-size: 1.125rem; font-weight: 500; margin-top: 0; margin-bottom: 0.5rem; text-align: left;" class="text-gray-900 dark:text-gray-100">Несохраненные изменения</h3>

                            <p style="margin-top: 0; margin-bottom: 1.5rem; font-size: 0.875rem; color: #6b7280; text-align: left; line-height: 1.25rem;" class="dark:text-gray-400">
                                Если закроете окно, изменения не будут сохранены.
                            </p>

                            <div style="display: flex; gap: 0.75rem; justify-content: space-between; width: 100%;">
                                <button @click="saveAndClose()"
                                        type="button"
                                        style="flex: 1; padding: 0.625rem 0; border-radius: 9999px; background-color: #F1F3FB; color: #6972F0; cursor: pointer; font-weight: 500; border: none; font-size: 0.875rem; text-align: center;">
                                    Сохранить
                                </button>
                                <button @click="forceClose()"
                                        type="button"
                                        style="flex: 1; padding: 0.625rem 0; border-radius: 9999px; background-color: #6972F0; color: #ffffff; cursor: pointer; font-weight: 500; border: none; font-size: 0.875rem; text-align: center;">
                                    Не сохранять
                                </button>
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </template>
</div>
