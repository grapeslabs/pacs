<style>
    @keyframes tgFadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes tgSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .chat-wrapper { width: 100%; }
    .chat-header {
        display: flex;
        justify-content: end;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    .chat-add-btn {
        color: #7E92F8;
        font-weight: 600;
        font-size: 0.875rem;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background: none;
        border: none;
        cursor: pointer;
    }
    .chat-add-btn:hover { color: #6b21a8; }

    .chat-list { display: flex; flex-direction: column; gap: 0.75rem; }
    .chat-item {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: tgFadeIn 0.3s ease-out;
    }

    .chat-col-id.chat-col-id { position: relative; flex: 1; width: 20vw; !important;}
    .chat-col-name.chat-col-name { flex: 1; width: 60vw; !important;}

    .chat-input.chat-input {
        width: 100% !important;
        border-radius: 0.5rem !important;
        border: 1px solid #d1d5db !important;
        font-family: inherit !important;
        font-size: 1rem !important;
        transition: all 0.2s ease !important;
        box-sizing: border-box !important;
    }

    .chat-input-icon.chat-input-icon {
        padding: 0.75rem 2.5rem 0.75rem 1rem !important;
    }
    .chat-input-std.chat-input-std {
        padding: 0.75rem 1rem !important;
    }

    .chat-status-success {
        border: 2px solid #22c55e !important;
        box-shadow: none !important;
    }
    .chat-status-error {
        border: 2px solid #ef4444 !important;
        box-shadow: none !important;
    }

    .chat-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: #ffffff;
        border-radius: 9999px;
        padding: 0.125rem;
        width: 1.25rem;
        height: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chat-test-btn {
        background-color: #7E92F8;
        color: #FFFFFF;
        height: 46px;
        padding: 0 1.5rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 90px;
        border: none;
        cursor: pointer;
    }

    .chat-test-btn-loading { background-color: #f1f5f9; cursor: not-allowed; }

    .chat-spinner { animation: tgSpin 1s linear infinite; width: 1.25rem; height: 1.25rem; color: #a855f7; }

    .chat-remove-btn {
        color: #cbd5e1; padding: 0.5rem; transition: color 0.2s;
        background: none; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .chat-remove-btn:hover { color: #ef4444; }
    .chat-remove-btn svg { width: 1.5rem; height: 1.5rem; }
</style>
<div x-data="{
        items: {{ json_encode($element->toValue() ?? []) }},
        name: '{{ $element->getColumn() }}',
        testUrl: '{{route('chats.test')}}',
        selectors: {
            service: '{{ $serviceFieldSelector }}',
            token: '{{ $tokenFieldSelector }}',
            apiUrl: '{{ $apiUrlFieldSelector }}'
        },
        init() {
            if (Array.isArray(this.items) && this.items.length > 0) {
                this.items = this.items.map(item => ({
                    chat_id: item.chat_id,
                    name: item.name || '',
                    status: 'default'
                }));
            } else {
                this.add();
            }
        },
        add() {
            this.items.push({ chat_id: '', name: '', status: 'default' });
        },
        remove(index) {
            this.items.splice(index, 1);
        },
        getClass(status) {
            if (status === 'success') return 'chat-status-success';
            if (status === 'error') return 'chat-status-error';
            return '';
        },
        async test(index) {
            const item = this.items[index];
            if (!item.chat_id) return;

            const serviceInput = document.querySelector(this.selectors.service);
            const tokenInput = document.querySelector(this.selectors.token);
            const apiUrlInput = document.querySelector(this.selectors.apiUrl);

            if (!serviceInput || !serviceInput.value || !tokenInput || !tokenInput.value) {
                if (typeof Toast === 'object') Toast.error('Заполните Сервис и Токен');
                else alert('Заполните Сервис и Токен');
                return;
            }

            item.status = 'loading';

            try {
                let result = await axios.get(this.testUrl, {
                    params: {
                        chat_id: item.chat_id,
                        service: serviceInput.value,
                        token: tokenInput.value,
                        api_url: apiUrlInput.value,
                    }
                });
                item.status = result.data.success ? 'success' : 'error';
            } catch (error) {
                item.status = 'error';
            }
        }
    }"
     class="chat-wrapper"
>
    <div class="chat-header">
        <button type="button" @click="add()" class="chat-add-btn">
            <span>+ Добавить чат</span>
        </button>
    </div>

    <div class="chat-list">
        <template x-for="(item, index) in items" :key="index">
            <div class="chat-item">

                <div class="chat-col-id">
                    <input type="text"
                           x-model="item.chat_id"
                           :name="`${name}[${index}][chat_id]`"
                           class="chat-input chat-input-icon"
                           :class="getClass(item.status)"
                           placeholder="ID чата (напр. -100...)"
                    >

                    <div x-show="item.status === 'success'" class="chat-icon" style="display: none" x-show.important="item.status === 'success'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="#FFFFFF">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 20C15.523 20 20 15.523 20 10C20 4.477 15.523 0 10 0C4.477 0 0 4.477 0 10C0 15.523 4.477 20 10 20ZM15.371 6.835C15.4178 6.78681 15.4544 6.72968 15.4786 6.66703C15.5028 6.60439 15.5142 6.5375 15.512 6.47037C15.5098 6.40324 15.4941 6.33725 15.4658 6.27632C15.4375 6.21539 15.3973 6.16078 15.3474 6.11575C15.2976 6.07072 15.2392 6.03619 15.1757 6.01423C15.1123 5.99227 15.045 5.98331 14.978 5.98791C14.911 5.99251 14.8456 6.01056 14.7857 6.04098C14.7259 6.07141 14.6727 6.11359 14.6295 6.165L8.64 12.7835L5.345 9.638C5.24912 9.54637 5.12077 9.49658 4.98819 9.49958C4.8556 9.50258 4.72963 9.55812 4.638 9.654C4.54637 9.74988 4.49657 9.87823 4.49958 10.0108C4.50258 10.1434 4.55812 10.2694 4.654 10.361L8.321 13.861L8.6925 14.216L9.037 13.835L15.371 6.835Z" fill="#2DD451"/>
                        </svg>
                    </div>
                    <div x-show="item.status === 'error'" class="chat-icon" style="display: none" x-show.important="item.status === 'error'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="#FFFFFF">
                            <path d="M10 15C10.2833 15 10.521 14.904 10.713 14.712C10.905 14.52 11.0007 14.2827 11 14C10.9993 13.7173 10.9033 13.48 10.712 13.288C10.5207 13.096 10.2833 13 10 13C9.71667 13 9.47933 13.096 9.288 13.288C9.09667 13.48 9.00067 13.7173 9 14C8.99933 14.2827 9.09533 14.5203 9.288 14.713C9.48067 14.9057 9.718 15.0013 10 15ZM10 11C10.2833 11 10.521 10.904 10.713 10.712C10.905 10.52 11.0007 10.2827 11 10V6C11 5.71667 10.904 5.47933 10.712 5.288C10.52 5.09667 10.2827 5.00067 10 5C9.71733 4.99933 9.48 5.09533 9.288 5.288C9.096 5.48067 9 5.718 9 6V10C9 10.2833 9.096 10.521 9.288 10.713C9.48 10.905 9.71733 11.0007 10 11ZM10 20C8.61667 20 7.31667 19.7373 6.1 19.212C4.88334 18.6867 3.825 17.9743 2.925 17.075C2.025 16.1757 1.31267 15.1173 0.788001 13.9C0.263335 12.6827 0.000667933 11.3827 1.26582e-06 10C-0.000665401 8.61733 0.262001 7.31733 0.788001 6.1C1.314 4.88267 2.02633 3.82433 2.925 2.925C3.82367 2.02567 4.882 1.31333 6.1 0.788C7.318 0.262667 8.618 0 10 0C11.382 0 12.682 0.262667 13.9 0.788C15.118 1.31333 16.1763 2.02567 17.075 2.925C17.9737 3.82433 18.6863 4.88267 19.213 6.1C19.7397 7.31733 20.002 8.61733 20 10C19.998 11.3827 19.7353 12.6827 19.212 13.9C18.6887 15.1173 17.9763 16.1757 17.075 17.075C16.1737 17.9743 15.1153 18.687 13.9 19.213C12.6847 19.739 11.3847 20.0013 10 20Z" fill="#F04138"/>
                        </svg>
                    </div>
                </div>

                <div class="chat-col-name">
                    <input type="text"
                           x-model="item.name"
                           :name="`${name}[${index}][name]`"
                           class="chat-input chat-input-std"
                           placeholder="Название (напр. Support)"
                    >
                </div>

                <button type="button"
                        @click="test(index)"
                        :disabled="!item.chat_id || item.status === 'loading'"
                        class="chat-test-btn"
                        :class="item.status === 'loading' ? 'chat-test-btn-loading' : 'chat-test-btn-default'"
                >
                    <span x-show="item.status !== 'loading'">Тест</span>
                    <svg x-show="item.status === 'loading'" class="chat-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </button>

                <button type="button" @click="remove(index)" class="chat-remove-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </template>
    </div>
</div>
