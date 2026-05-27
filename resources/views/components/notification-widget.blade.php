<style>
    .pacs-widget-wrapper {
        background-color: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        padding: 1.5rem;
        font-family: inherit;
    }

    .dark .pacs-widget-wrapper {
        background-color: #1e293b;
    }

    .pacs-widget-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.25rem;
    }

    .pacs-widget-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #0f172a;
    }

    .dark .pacs-widget-title {
        color: #f8fafc;
    }

    .pacs-widget-badge {
        background-color: #818cf8;
        color: #ffffff;
        border-radius: 9999px;
        padding: 0.125rem 0.6rem;
        font-size: 0.875rem;
        font-weight: 500;
        margin-left: 0.75rem;
        transition: opacity 0.5s ease;
    }

    .pacs-widget-list {
        max-height: 350px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        padding-right: 0.5rem;
    }

    .dark .pacs-widget-list {
        scrollbar-color: #475569 transparent;
    }

    .pacs-widget-list::-webkit-scrollbar {
        width: 6px;
    }

    .pacs-widget-list::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 10px;
    }

    .dark .pacs-widget-list::-webkit-scrollbar-thumb {
        background-color: #475569;
    }

    .pacs-widget-item {
        display: flex;
        align-items: flex-start;
        padding: 1rem 0.5rem;
        border-bottom: 1px dashed #e2e8f0;
        transition: background-color 0.5s ease;
        border-radius: 8px;
    }

    .dark .pacs-widget-item {
        border-bottom-color: #334155;
    }

    .pacs-widget-item:last-child {
        border-bottom: none;
    }

    .pacs-widget-item.pacs-highlighted {
        background-color: #fefce8;
    }

    .dark .pacs-widget-item.pacs-highlighted {
        background-color: rgba(253, 224, 71, 0.1);
    }

    .pacs-widget-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-right: 1rem;
        margin-top: 0.125rem;
    }

    .pacs-widget-icon.error {
        background-color: #fef2f2;
        color: #ef4444;
    }

    .pacs-widget-icon.warning {
        background-color: #fffbeb;
        color: #f59e0b;
    }

    .pacs-widget-icon.info {
        background-color: #eef2ff;
        color: #6366f1;
    }

    .dark .pacs-widget-icon.error { background-color: rgba(239, 68, 68, 0.2); }
    .dark .pacs-widget-icon.warning { background-color: rgba(245, 158, 11, 0.2); }
    .dark .pacs-widget-icon.info { background-color: rgba(99, 102, 241, 0.2); }

    .pacs-widget-content {
        flex-grow: 1;
        font-size: 0.875rem;
        line-height: 1.5;
        color: #334155;
    }

    .dark .pacs-widget-content {
        color: #cbd5e1;
    }

    .pacs-widget-time {
        font-size: 0.75rem;
        color: #94a3b8;
        white-space: nowrap;
        margin-left: 1rem;
        flex-shrink: 0;
        margin-top: 0.125rem;
    }

    .pacs-link {
        color: #6366f1;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .pacs-link:hover {
        color: #4f46e5;
        text-decoration: underline;
    }

    .dark .pacs-link {
        color: #818cf8;
    }

    .dark .pacs-link:hover {
        color: #a5b4fc;
    }
</style>

<div class="pacs-widget-wrapper"
     x-data="{
         unreadIds: {{ json_encode($unreadIds) }},
         showBadge: {{ $unreadCount > 0 ? 'true' : 'false' }}
     }"
     x-init="setTimeout(() => { unreadIds = []; showBadge = false; }, 3000)"
     x-cloak>

    <div class="pacs-widget-header">
        <h2 class="pacs-widget-title">Уведомления</h2>
        <span class="pacs-widget-badge"
              x-show="showBadge"
              x-transition.opacity>
            {{ $unreadCount }}
        </span>
    </div>

    <div class="pacs-widget-list">
        @forelse($notifications as $notification)
            <div class="pacs-widget-item"
                 :class="{ 'pacs-highlighted': unreadIds.includes('{{ $notification['id'] }}') }">

                <div class="pacs-widget-icon {{ $notification['type'] }}">
                    @if($notification['type'] === 'error')
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.66667 10C6.85556 10 7.014 9.936 7.142 9.808C7.27 9.68 7.33378 9.52178 7.33333 9.33333C7.33289 9.14489 7.26889 8.98667 7.14133 8.85867C7.01378 8.73067 6.85556 8.66667 6.66667 8.66667C6.47778 8.66667 6.31956 8.73067 6.192 8.85867C6.06445 8.98667 6.00045 9.14489 6 9.33333C5.99956 9.52178 6.06356 9.68022 6.192 9.80867C6.32045 9.93711 6.47867 10.0009 6.66667 10ZM6.66667 7.33333C6.85556 7.33333 7.014 7.26933 7.142 7.14133C7.27 7.01333 7.33378 6.85511 7.33333 6.66667V4C7.33333 3.81111 7.26933 3.65289 7.14133 3.52533C7.01333 3.39778 6.85511 3.33378 6.66667 3.33333C6.47822 3.33289 6.32 3.39689 6.192 3.52533C6.064 3.65378 6 3.812 6 4V6.66667C6 6.85556 6.064 7.014 6.192 7.142C6.32 7.27 6.47822 7.33378 6.66667 7.33333ZM6.66667 13.3333C5.74445 13.3333 4.87778 13.1582 4.06667 12.808C3.25556 12.4578 2.55 11.9829 1.95 11.3833C1.35 10.7838 0.875112 10.0782 0.525334 9.26667C0.175556 8.45511 0.000445288 7.58844 8.43882e-07 6.66667C-0.000443601 5.74489 0.174668 4.87822 0.525334 4.06667C0.876001 3.25511 1.35089 2.54956 1.95 1.95C2.54911 1.35044 3.25467 0.875556 4.06667 0.525333C4.87867 0.175111 5.74533 0 6.66667 0C7.588 0 8.45467 0.175111 9.26667 0.525333C10.0787 0.875556 10.7842 1.35044 11.3833 1.95C11.9824 2.54956 12.4576 3.25511 12.8087 4.06667C13.1598 4.87822 13.3347 5.74489 13.3333 6.66667C13.332 7.58844 13.1569 8.45511 12.808 9.26667C12.4591 10.0782 11.9842 10.7838 11.3833 11.3833C10.7824 11.9829 10.0769 12.458 9.26667 12.8087C8.45644 13.1593 7.58978 13.3342 6.66667 13.3333Z" fill="#F04138"/>
                        </svg>
                    @elseif($notification['type'] === 'warning')
                        <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.82004 1.27502C5.77605 -0.425006 8.22409 -0.425006 9.1801 1.27502L13.6762 9.27914C14.6122 10.9462 13.4072 13.0042 11.4961 13.0042H2.50301C0.591978 13.0042 -0.61204 10.9462 0.323974 9.27914L4.82004 1.27502ZM7.00007 8.50012C6.80116 8.50012 6.61039 8.57914 6.46973 8.7198C6.32908 8.86045 6.25006 9.05122 6.25006 9.25014C6.25006 9.44905 6.32908 9.63982 6.46973 9.78047C6.61039 9.92113 6.80116 10.0001 7.00007 10.0001C7.19899 10.0001 7.38975 9.92113 7.53041 9.78047C7.67106 9.63982 7.75008 9.44905 7.75008 9.25014C7.75008 9.05122 7.67106 8.86045 7.53041 8.7198C7.38975 8.57914 7.19899 8.50012 7.00007 8.50012ZM7.00007 4.00006C6.86746 4.00006 6.74028 4.05274 6.64651 4.14651C6.55274 4.24028 6.50006 4.36746 6.50006 4.50007V7.0001C6.50006 7.13271 6.55274 7.25989 6.64651 7.35366C6.74028 7.44743 6.86746 7.50011 7.00007 7.50011C7.13268 7.50011 7.25986 7.44743 7.35363 7.35366C7.4474 7.25989 7.50008 7.13271 7.50008 7.0001V4.50007C7.50008 4.36746 7.4474 4.24028 7.35363 4.14651C7.25986 4.05274 7.13268 4.00006 7.00007 4.00006Z" fill="#FFBB00"/>
                        </svg>
                    @else
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.665 0C2.9841 0 0 2.9841 0 6.665C0 10.3459 2.9841 13.33 6.665 13.33C10.3459 13.33 13.33 10.3459 13.33 6.665C13.33 2.9841 10.3459 0 6.665 0ZM6.36205 3.02955C6.20135 3.02955 6.04723 3.09338 5.9336 3.20701C5.81997 3.32064 5.75614 3.47476 5.75614 3.63545C5.75614 3.79615 5.81997 3.95027 5.9336 4.0639C6.04723 4.17753 6.20135 4.24136 6.36205 4.24136H6.665C6.8257 4.24136 6.97981 4.17753 7.09344 4.0639C7.20707 3.95027 7.27091 3.79615 7.27091 3.63545C7.27091 3.47476 7.20707 3.32064 7.09344 3.20701C6.97981 3.09338 6.8257 3.02955 6.665 3.02955H6.36205ZM5.45318 5.45318C5.29248 5.45318 5.13837 5.51702 5.02474 5.63065C4.91111 5.74428 4.84727 5.89839 4.84727 6.05909C4.84727 6.21979 4.91111 6.3739 5.02474 6.48753C5.13837 6.60116 5.29248 6.665 5.45318 6.665H6.05909V8.48273H5.45318C5.29248 8.48273 5.13837 8.54656 5.02474 8.66019C4.91111 8.77382 4.84727 8.92794 4.84727 9.08864C4.84727 9.24933 4.91111 9.40345 5.02474 9.51708C5.13837 9.63071 5.29248 9.69455 5.45318 9.69455H7.87682C8.03752 9.69455 8.19163 9.63071 8.30526 9.51708C8.41889 9.40345 8.48273 9.24933 8.48273 9.08864C8.48273 8.92794 8.41889 8.77382 8.30526 8.66019C8.19163 8.54656 8.03752 8.48273 7.87682 8.48273H7.27091V6.05909C7.27091 5.89839 7.20707 5.74428 7.09344 5.63065C6.97981 5.51702 6.8257 5.45318 6.665 5.45318H5.45318Z" fill="#7381F4"/>
                        </svg>
                    @endif
                </div>

                <div class="pacs-widget-content">
                    {!! $notification['html_message'] !!}
                </div>

                <div class="pacs-widget-time">
                    {{ $notification['date'] }}
                </div>
            </div>
        @empty
            <div class="pacs-widget-content" style="padding: 1rem 0; text-align: center; color: #94a3b8;">
                Нет уведомлений
            </div>
        @endforelse
    </div>
</div>
