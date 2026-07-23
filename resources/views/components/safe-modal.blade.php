@props([
    'name' => 'safe-modal',
    'title' => '',
])

<style>
    #{{ $name }}_content form button[type="submit"] { display: none !important; }
    #{{ $name }}_content .mt-4:has(> button[type="submit"]:only-child) { display: none !important; }
</style>

<div
    x-data="{
        open: false,
        isDirty: false,
        showConfirm: false,
        modalId: '{{ $name }}',
        modalTitle: '{{ is_callable($title) ? $title() : $title }}',
        modalSubtitle: '',
        submitLabel: 'Сохранить',
        submitIcon: 'check',

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
            modalSubtitle = (typeof $event.detail === 'object' && $event.detail.subtitle)
                ? $event.detail.subtitle
                : '';
            submitLabel = (typeof $event.detail === 'object' && $event.detail.submitLabel)
                ? $event.detail.submitLabel
                : 'Сохранить';
            submitIcon = (typeof $event.detail === 'object' && $event.detail.submitIcon)
                ? $event.detail.submitIcon
                : 'check';
            toggleModal();
        }
    "
    @toast.window="if(open && (event.detail.type === 'success' || event.detail.type === 'warning')) { forceClose(); }"
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
                 style="position: absolute; top: 0; right: 0; bottom: 0; width: 33vw; min-width: 420px; max-width: 560px; background-color: #ffffff; box-shadow: -10px 0 25px -5px rgba(0, 0, 0, 0.15); pointer-events: auto;"
                 class="safe-modal-panel dark:bg-moonshine-dark">

                <div style="display: flex; flex-direction: column; height: 100vh; max-height: 100vh; width: 100%;">

                    <div style="display: flex; align-items: flex-start; justify-content: space-between; padding: 1.5rem 1.75rem 1.25rem; flex-shrink: 0; border-bottom: 1px solid #EEF0F6;">
                        <div style="min-width: 0;">
                            <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #1F2430; line-height: 1.25;" class="dark:text-gray-100" x-text="modalTitle"></h3>
                            <p x-show="modalSubtitle" style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: #8A8FA6;" class="dark:text-gray-400" x-text="modalSubtitle"></p>
                        </div>
                        <button @click="tryClose()" type="button" style="background: transparent; border: none; cursor: pointer; padding: 0.25rem; color: #A1A5B7; flex-shrink: 0;" class="hover:text-gray-500">
                            <x-moonshine::icon icon="x-mark" size="6" />
                        </button>
                    </div>

                    <div style="flex: 1 1 0%; min-height: 0; overflow-y: auto; overscroll-behavior: contain; position: relative;">
                        <div id="{{ $name }}_content" style="padding: 1.75rem;" @input="isDirty = true" @change="isDirty = true">
                            {{ $slot ?? '' }}
                        </div>
                    </div>

                    <div class="safe-modal-footer" style="flex-shrink: 0; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.75rem; border-top: 1px solid #EEF0F6; background: #fff;">
                        <button @click="tryClose()" type="button"
                                style="padding: 0.7rem 1.5rem; border-radius: 9999px; background: #fff; color: #4E4E61; border: 1px solid #E1E4F3; cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                            Отмена
                        </button>
                        <button @click="saveAndClose()" type="button"
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.6rem; border-radius: 9999px; background: #6972F0; color: #fff; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                            <svg x-show="submitIcon === 'check'" width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <svg x-show="submitIcon === 'addkey'" x-cloak width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M15 5H19.5C20.435 5 20.902 5 21.25 5.201C21.478 5.33265 21.6674 5.52199 21.799 5.75C22 6.098 22 6.565 22 7.5C22 8.435 22 8.902 21.799 9.25C21.6674 9.47801 21.478 9.66735 21.25 9.799C20.902 10 20.435 10 19.5 10H15M13 14H19.5C20.435 14 20.902 14 21.25 14.201C21.478 14.3326 21.6674 14.522 21.799 14.75C22 15.098 22 15.565 22 16.5C22 17.435 22 17.902 21.799 18.25C21.6674 18.478 21.478 18.6674 21.25 18.799C20.902 19 20.435 19 19.5 19H13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 11.584C9.3 12.271 10.3256 11.8234 11.8111 10.15C12.4239 9.21373 12.75 8.119 12.75 7C12.75 5.47501 12.1442 4.01247 11.0659 2.93414C9.98753 1.8558 8.52499 1.25 7 1.25C5.47501 1.25 4.01247 1.8558 2.93414 2.93414C1.8558 4.01247 1.25 5.47501 1.25 7C1.25 8.119 1.57611 9.21373 2.18892 10.15C3.67442 11.8234 4.7 12.271 5 11.584V19.586C5 19.933 5.055 21.53 5.586 21C6.116 20.47 7 20.47 7 20.47C7 20.47 7.884 20.47 8.414 21C8.944 21.53 9 19.933 9 19.586V11.584Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                            <span x-text="submitLabel">Сохранить</span>
                        </button>
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
