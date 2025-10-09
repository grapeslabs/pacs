<div x-data="photoField()"
     x-init="initField(@js($element->getFullPathValues()), '{{ $element->getColumn() }}')"
     @keydown.escape.window="closeCamera"
     @paste.window="handleGlobalPaste($event)"
     @dragover.window.prevent="isDragOver = true"
     @dragleave.window.prevent="if (!$event.relatedTarget || $event.relatedTarget.nodeName === 'HTML') isDragOver = false"
     @drop.window.prevent="handleDrop($event)"
     class="relative group w-full"
>
    <div class="relative w-full rounded-xl min-h-[160px]">

        <div x-show="isDragOver"
             x-transition.opacity
             class="absolute top-0 left-0 w-full h-full z-50 flex flex-col items-center justify-center bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm border-2 border-primary border-dashed rounded-xl pointer-events-none"
        >
            <x-moonshine::icon icon="arrow-down-tray" class="w-12 h-12 text-primary animate-bounce" />
            <span class="mt-2 text-lg font-bold text-primary">Отпустите файлы здесь</span>
        </div>

        <div class="flex flex-wrap gap-4 items-start justify-start p-4 rounded-xl border-2 transition-all min-h-[160px]"
             :class="isDragOver ? 'border-primary/30 bg-gray-50 dark:bg-gray-800' : 'border-transparent bg-gray-50/50 dark:bg-gray-900/50'">

            <div @click="$refs.manualInput.click()"
                 class="relative w-32 h-32 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-primary hover:bg-white dark:hover:bg-gray-800 transition-all cursor-pointer shrink-0 text-gray-400 hover:text-primary"
            >
                <x-moonshine::icon icon="plus" class="w-8 h-8" />
                <span class="mt-2 text-[10px] font-bold uppercase tracking-wider">Загрузить</span>
                <input type="file" x-ref="manualInput" multiple accept="image/*" class="hidden" @change="handleManualFileSelect">
            </div>

            <template x-for="(item, index) in items" :key="item.id">
                <div class="relative w-32 h-32 shrink-0 group/item">

                    <div class="w-full h-full rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm bg-white dark:bg-gray-800">
                        <img :src="item.url" class="w-full h-full object-cover transition-transform group-hover/item:scale-105">
                    </div>

                    <button type="button"
                            @click.prevent="removeFile(index)"
                            class="absolute -top-2 -right-2 z-50 flex items-center justify-center w-7 h-7 rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 transition-all opacity-100 lg:opacity-0 lg:group-hover/item:opacity-100 cursor-pointer"
                            title="Удалить фото"
                    >
                        <x-moonshine::icon icon="x-mark" class="w-4 h-4" />
                    </button>

                    </div>
            </template>
        </div>
    </div>

    <template x-for="item in items" :key="'hidden-'+item.id">
        <template x-if="!item.isNew">
            <input type="hidden" :name="'hidden_' + columnName + '[]'" :value="item.value">
        </template>
    </template>

    <input type="file" x-ref="fileInput" :name="columnName + '[]'" multiple class="hidden">
</div>
