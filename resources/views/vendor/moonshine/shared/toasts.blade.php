<style>
    .toast:not(.ms-custom-toast),
    .toast-success,
    .toast-error,
    .toast-info,
    .toast-container:not(.ms-custom-toast-container),
    [x-data="toast"]:not(.ms-custom-toast-container) {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    .ms-custom-toast-container {
        position: fixed;
        top: 2rem;
        right: 2rem;
        z-index: 999999;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        pointer-events: none;
    }

    .ms-custom-toast {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 12px 16px;
        gap: 10px;
        min-width: 280px;
        max-width: 340px;
        min-height: 54px;
        background: linear-gradient(180deg, #7E92F8 0%, #6972F0 100%);
        border-radius: 12px;
        pointer-events: auto;
        transform-origin: top right;
        box-sizing: border-box;
        box-shadow: 0 10px 25px -5px rgba(121, 133, 251, 0.4);
    }

    .ms-custom-toast-icon {
        width: 30px;
        height: 30px;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ms-custom-toast-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        flex-grow: 1;
        gap: 4px;
    }

    .ms-custom-toast-header {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 8px;
        width: 100%;
    }

    .ms-custom-toast-title {
        font-family: 'Mulish', sans-serif;
        font-weight: 600;
        font-size: 14px;
        line-height: 18px;
        letter-spacing: 0.005em;
        color: #FFFFFF;
        flex-grow: 1;
        word-break: break-word;
    }

    .ms-custom-toast-close {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        flex: none;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .ms-custom-toast-close:hover {
        opacity: 1;
    }

    .ms-custom-toast-undo {
        font-family: 'Mulish', sans-serif;
        font-weight: 300;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0.005em;
        text-decoration-line: underline;
        color: #FFFFFF;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        text-align: left;
    }
</style>

<div x-data="{
        toasts: [],
        init() {
            window.addEventListener('toast', (e) => {
                this.addToast(e.detail);
            });
            window.addEventListener('moonshine:toast', (e) => {
                this.addToast(e.detail);
            });
            const sessionToast = document.querySelector('meta[name=\'moonshine-custom-toast\']');
            if (sessionToast && sessionToast.content) {
                try {
                    this.addToast(JSON.parse(sessionToast.content));
                } catch (e) {}
            }
        },
        addToast(data) {
            const message = data.message || data.text || data.title || 'Выполнено';
            const hasUndo = !!data.undoUrl;
            const isGenericDelete = message === 'Удалено';

            if (hasUndo) {
                const genericToast = this.toasts.find(t => t.message === 'Удалено');
                if (genericToast) {
                    this.removeToast(genericToast.id);
                }
            }

            if (isGenericDelete) {
                const hasUndoToast = this.toasts.some(t => !!t.undoUrl);
                if (hasUndoToast) {
                    return;
                }
            }

            const id = Date.now() + Math.random();
            this.toasts.push({
                id: id,
                message: message,
                undoUrl: data.undoUrl || null,
                visible: true
            });
            setTimeout(() => this.removeToast(id), hasUndo ? 10000 : 4000);
        },
        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index > -1) {
                this.toasts[index].visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300);
            }
        },
        executeUndo(toast) {
            if (!toast.undoUrl) return;
            fetch(toast.undoUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content,
                    'Accept': 'application/json'
                }
            }).then(res => {
                if (res.ok) {
                    this.removeToast(toast.id);
                    this.addToast({ message: 'Элемент восстановлен' });
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        }
    }"
     class="ms-custom-toast-container"
     x-cloak
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="ms-custom-toast"
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
        >
            <div class="ms-custom-toast-icon">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.5186 0C26.6504 0 29.9998 3.34252 30 7.46582C30 11.578 26.6691 14.9134 22.5527 14.9316C22.5529 14.9544 22.5547 14.9772 22.5547 15C22.5547 15.0227 22.5529 15.0457 22.5527 15.0684C26.6691 15.0866 30 18.422 30 22.5342C30 26.6576 26.6505 30 22.5186 30C18.3867 29.9999 15.0371 26.6576 15.0371 22.5342C15.0371 22.5202 15.038 22.5062 15.0381 22.4922L15.0361 22.4932C15.011 22.4932 14.986 22.4915 14.9609 22.4912C14.961 22.5055 14.9629 22.5199 14.9629 22.5342C14.9629 26.6576 11.6134 30 7.48145 30C3.34956 29.9999 3.61531e-05 26.6576 0 22.5342C0 18.4108 3.34954 15.0684 7.48145 15.0684C7.49377 15.0684 7.50625 15.0683 7.51855 15.0684C7.51835 15.0457 7.51758 15.0227 7.51758 15C7.51758 14.9772 7.51835 14.9544 7.51855 14.9316C7.50625 14.9317 7.49377 14.9326 7.48145 14.9326C3.34954 14.9325 0 11.5892 0 7.46582C0.000216905 3.34258 3.34968 8.89796e-05 7.48145 0C11.6133 0 14.9627 3.34252 14.9629 7.46582C14.9629 7.48002 14.961 7.49461 14.9609 7.50879C14.986 7.50854 15.011 7.50781 15.0361 7.50781H15.0381C15.038 7.49396 15.0371 7.47969 15.0371 7.46582C15.0373 3.34258 18.3868 8.89796e-05 22.5186 0Z" fill="white"/>
                    <rect width="16" height="16" transform="translate(7 7)" fill="white"/>
                    <path d="M10.334 15.668L13.0007 18.3346L19.6673 11.668" stroke="#7381F4" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="ms-custom-toast-content">
                <div class="ms-custom-toast-header">
                    <div class="ms-custom-toast-title" x-text="toast.message"></div>
                    <button type="button" class="ms-custom-toast-close" @click="removeToast(toast.id)">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 4L4 12M4 4L12 12" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <template x-if="toast.undoUrl">
                    <button type="button" class="ms-custom-toast-undo" @click="executeUndo(toast)">
                        Отменить действие
                    </button>
                </template>
            </div>
        </div>
    </template>
    @if(session()->has('moonshine_custom_toast'))
        <meta name="moonshine-custom-toast" content="{{ json_encode(session()->get('moonshine_custom_toast')) }}">
        @php session()->forget('moonshine_custom_toast') @endphp
    @endif
</div>
