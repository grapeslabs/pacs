@props([
    'name' => 'filter-sidebar',
    'title' => 'Фильтры',
    'subtitle' => '',
    'form' => null,
    'count' => 0,
])

<script>
    document.addEventListener('alpine:init', () => {
        if (! Alpine.store('filters')) {
            Alpine.store('filters', { count: {{ (int) $count }} });
        }
    });
</script>

<div
    x-data="{
        open: false,
        modalId: '{{ $name }}',

        registerInStack() { Alpine.store('overlays')?.register(this.modalId, () => this.closeModal()); },
        unregisterFromStack() { Alpine.store('overlays')?.unregister(this.modalId); },

        toggleModal() {
            this.open = !this.open;
            this.open ? this.registerInStack() : this.unregisterFromStack();
            this.toggleBodyScroll();
        },

        closeModal() {
            this.open = false;
            this.unregisterFromStack();
            this.toggleBodyScroll();
        },

        countActiveFilters() {
            const form = document.querySelector(`#${this.modalId}_content form`);
            if (! form) { return 0; }

            const seen = new Set();
            form.querySelectorAll('[name^=\'filter[\']').forEach((el) => {
                const name = el.getAttribute('name');
                if (! name) { return; }
                if ((el.type === 'checkbox' || el.type === 'radio') && ! el.checked) { return; }
                const value = el.value;
                if (value === '' || value === null || value === '0') { return; }
                seen.add(name.replace(/\]\[.*$/, ']'));
            });

            return seen.size;
        },

        applyFilters() {
            const form = document.querySelector(`#${this.modalId}_content form`);
            if (form) {
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        },

        onFiltersSubmit() {
            Alpine.store('filters').count = this.countActiveFilters();
            this.closeModal();
        },

        resetFilters() {
            const reset = document.querySelector(`#${this.modalId}_content .js-async-reset-button`);
            if (reset) {
                reset.click();
            }
            Alpine.store('filters').count = 0;
            this.closeModal();
        },

        toggleBodyScroll() {
            document.body.style.overflow = this.open ? 'hidden' : '';
        }
    }"
    @modal-toggled.window="
        let targetId = typeof $event.detail === 'object' ? $event.detail.id : $event.detail;
        if (targetId === modalId) { toggleModal(); }
    "
    @toast.window="if (open && $event.detail.type === 'success') { closeModal(); }"
>
    <template x-teleport="body">

        <div x-show="open"
             style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 500; pointer-events: none;"
             role="dialog"
             aria-modal="true">

            <div x-show="open"
                 x-transition.opacity.duration.300ms
                 @click="closeModal()"
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
                            <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0; line-height: 1.4;" class="text-gray-900 dark:text-gray-100">{{ is_callable($title) ? $title() : $title }}</h3>
                            @if($subtitle)
                                <p style="font-size: 0.8125rem; color: #7F7F9D; margin: 0.125rem 0 0;">{{ is_callable($subtitle) ? $subtitle() : $subtitle }}</p>
                            @endif
                        </div>
                        <button @click="closeModal()" type="button" style="background: transparent; border: none; cursor: pointer; padding: 0.25rem;" class="text-gray-400 hover:text-gray-500">
                            <x-moonshine::icon icon="x-mark" size="6" />
                        </button>
                    </div>

                    <div style="flex: 1 1 0%; min-height: 0; overflow-y: auto; overscroll-behavior: contain; position: relative;">
                        <div id="{{ $name }}_content" style="padding: 1.5rem;" @submit="onFiltersSubmit()">
                            {!! $form !!}
                        </div>
                        <style>
                            #{{ $name }}_content .btn-primary,
                            #{{ $name }}_content .js-async-reset-button,
                            #{{ $name }}_content [type="submit"] { display: none !important; }
                        </style>
                    </div>

                    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid #EAEDFB;">
                        <button @click="resetFilters()"
                                type="button"
                                style="padding: 0.625rem 1.5rem; border-radius: 9999px; background-color: #ffffff; color: #4E4E61; cursor: pointer; font-weight: 600; border: 1px solid #D7D0EF; font-size: 0.875rem;">
                            Сбросить
                        </button>
                        <button @click="applyFilters()"
                                type="button"
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.5rem; border-radius: 9999px; background-color: #6972F0; color: #ffffff; cursor: pointer; font-weight: 700; border: none; font-size: 0.875rem;">
                            Применить
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>
