@if(\Illuminate\Support\Facades\Cache::get('drive_limit_stoped', false))
    <style>
        .layout-navigation {
            position: relative !important;
        }
    </style>

    <template x-teleport=".layout-navigation" data-teleport-template="true">
        <div class="absolute left-1/2 top-1/2 z-[100] -translate-x-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none w-full px-4">
            <div x-data="{ show: true }"
                 x-show="show"
                 x-transition.opacity=""
                 class="pointer-events-auto flex items-center justify-between gap-4 px-4 py-2 text-[13px] sm:text-[14px] font-medium text-white shadow-md rounded-md"
                 style="background-color: #ef4444; width: max-content; max-width: 100%; height: 50px">
                <span>
                    Видеопотоки отключены — недостаточно места на диске. Освободите место, чтобы восстановить работу.
                </span>
                <button @click="show = false" type="button" class="flex-shrink-0 text-white/80 hover:text-white transition-colors focus:outline-none ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

        </div>
    </template>
@endif
