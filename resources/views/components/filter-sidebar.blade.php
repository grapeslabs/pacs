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

                    <div style="display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 24px; flex-shrink: 0; border-bottom: 1px solid #D7DBEF;">
                        <div style="min-width: 0;">
                            <h3 style="font-size: 19px; font-weight: 800; margin: 0; line-height: 1.25; color: #1E1E1E;">{{ is_callable($title) ? $title() : $title }}</h3>
                            @if($subtitle)
                                <p style="font-size: 13px; font-weight: 600; color: #6B7390; margin: 3px 0 0;">{{ is_callable($subtitle) ? $subtitle() : $subtitle }}</p>
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

                    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #D7DBEF;">
                        <button @click="resetFilters()"
                                type="button"
                                class="fs-btn fs-btn--ghost">
                            Сбросить
                        </button>
                        <button @click="applyFilters()"
                                type="button"
                                class="fs-btn fs-btn--primary">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" style="flex:0 0 auto;">
                                <path d="M18.4375 6.5625H1.5625C1.31386 6.5625 1.0754 6.46373 0.899587 6.28791C0.723772 6.1121 0.625 5.87364 0.625 5.625C0.625 5.37636 0.723772 5.1379 0.899587 4.96209C1.0754 4.78627 1.31386 4.6875 1.5625 4.6875H18.4375C18.6861 4.6875 18.9246 4.78627 19.1004 4.96209C19.2762 5.1379 19.375 5.37636 19.375 5.625C19.375 5.87364 19.2762 6.1121 19.1004 6.28791C18.9246 6.46373 18.6861 6.5625 18.4375 6.5625ZM15.3125 10.9375H4.6875C4.43886 10.9375 4.2004 10.8387 4.02459 10.6629C3.84877 10.4871 3.75 10.2486 3.75 10C3.75 9.75136 3.84877 9.5129 4.02459 9.33709C4.2004 9.16127 4.43886 9.0625 4.6875 9.0625H15.3125C15.5611 9.0625 15.7996 9.16127 15.9754 9.33709C16.1512 9.5129 16.25 9.75136 16.25 10C16.25 10.2486 16.1512 10.4871 15.9754 10.6629C15.7996 10.8387 15.5611 10.9375 15.3125 10.9375ZM11.5625 15.3125H8.4375C8.18886 15.3125 7.9504 15.2137 7.77459 15.0379C7.59877 14.8621 7.5 14.6236 7.5 14.375C7.5 14.1264 7.59877 13.8879 7.77459 13.7121C7.9504 13.5363 8.18886 13.4375 8.4375 13.4375H11.5625C11.8111 13.4375 12.0496 13.5363 12.2254 13.7121C12.4012 13.8879 12.5 14.1264 12.5 14.375C12.5 14.6236 12.4012 14.8621 12.2254 15.0379C12.0496 15.2137 11.8111 15.3125 11.5625 15.3125Z" fill="currentColor"/>
                            </svg>
                            Применить
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </template>

    <style>
        .fs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; padding: 0 22px; border-radius: 9999px; font-size: 14px; cursor: pointer; border: 1px solid transparent; font-family: inherit; transition: background .15s, filter .15s; }
        .fs-btn--ghost { background: transparent; color: #4E4E61; font-weight: 600; }
        .fs-btn--ghost:hover { background: #EAEDF8; }
        .fs-btn--primary { background: linear-gradient(180deg, #7E92F8 0%, #6972F0 100%); color: #fff; font-weight: 700; }
        .fs-btn--primary:hover { filter: brightness(.95); }
        .fs-btn--primary:active { filter: brightness(.88); }
    </style>
</div>
