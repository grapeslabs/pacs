<div class="split-export-wrapper" x-data="{ open: false }" @click.outside="open = false" x-cloak>
    <style>
        .split-export-wrapper {
            display: inline-flex;
            align-items: stretch;
            position: relative;
        }
        .split-btn-main {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.2) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-grow: 1;
        }
        .split-btn-drop {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .split-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            margin-top: 0.5rem;
            z-index: 50;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.05);
            padding: 0.5rem;
            border-radius: 0.75rem;
            box-sizing: border-box;
        }
        .split-arrow {
            transition: transform 0.2s ease-in-out;
        }
        .split-arrow-open {
            transform: rotate(180deg);
        }
        .split-dropdown-item {
            display: block;
            width: 100%;
            text-align: left;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: background-color 0.15s ease-in-out;
            margin-bottom: 0.25rem;
            text-decoration: none;
        }
        .split-dropdown-item:last-child {
            margin-bottom: 0;
        }
        .split-dropdown-item:hover {
            background-color: #f3f4f6 !important;
        }
    </style>

    <a href="{{ $exportUrl }}" target="_blank" class="btn btn-primary split-btn-main" title="Экспорт фотографий персон">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        Экспорт
    </a>

    <button type="button" @click.prevent="open = !open" class="btn btn-primary split-btn-drop" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" class="split-arrow w-4 h-4" :class="open ? 'split-arrow-open' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="split-dropdown bg-white border border-gray-100">
        <a href="{{ $exportUrl }}" target="_blank" class="split-dropdown-item text-gray-700">
            Экспорт
        </a>
        <button type="button" class="split-dropdown-item text-gray-700"
                @click="open = false; $refs.importFile.click()">
            Импорт
        </button>
    </div>

    {{-- Импорт без модалки: filepicker открывается сразу, выбор файла сразу отправляет форму --}}
    <form x-ref="importForm" action="{{ $importUrl }}" method="POST" enctype="multipart/form-data" style="display:none;">
        @csrf
        <input type="file" name="archive" accept=".zip" x-ref="importFile"
               @change="$refs.importFile.files.length && $refs.importForm.submit()">
    </form>
</div>
