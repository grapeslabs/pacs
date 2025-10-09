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
    @submit.window="if(open) { forceClose(); }"
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
                     style="display: none; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; background-color: rgba(255, 255, 255, 0.95);"
                     class="dark:bg-moonshine-dark/95">

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 1rem; border-radius: 50px; padding: 20px; background-color: #fef3c7;">
                        <x-moonshine::icon icon="exclamation-triangle" class="h-8 w-8 text-yellow-600 dark:text-yellow-500" />
                        <h3 style="font-size: 24px; font-weight: 700; margin: 0;">Есть несохраненные изменения</h3>
                        <x-moonshine::icon icon="exclamation-triangle" class="h-8 w-8 text-yellow-600 dark:text-yellow-500" />
                    </div>

                    <p style="margin-bottom: 2rem;" class="text-gray-500 dark:text-gray-400">
                        Вы внесли данные, но не сохранили их. Если вы закроете панель сейчас, эти данные будут потеряны.
                    </p>

                    <div style="display: flex; gap: 0.75rem; width: 100%; justify-content: center;">
                        <button @click="showConfirm = false" style="padding: 20px; border: 1px solid #d1d5db; border-radius: 20px; background-color: transparent; cursor: pointer; font-weight: 500;">Отмена</button>
                        <button @click="forceClose()" style="padding: 20px; border: none; border-radius: 20px; background-color: #dc2626; color: #ffffff; cursor: pointer; font-weight: 500;">Закрыть без сохранения</button>
                    </div>
                </div>

            </div>
        </div>
    </template>
</div>
