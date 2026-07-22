<style>
    @keyframes rcpFadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes rcpSpin { to { transform: rotate(360deg); } }

    .rcp-wrap { width: 100%; }

    .rcp-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
    .rcp-list:empty { margin-bottom: 0; }

    .rcp-item {
        display: flex; align-items: center; gap: 12px;
        background: #F1F3FB; border: 1px solid #E5E7F5; border-radius: 14px;
        padding: 10px 12px; animation: rcpFadeIn .25s ease-out;
        transition: border-color .2s, box-shadow .2s;
    }
    .rcp-item.is-success { border-color: #43D15D; }
    .rcp-item.is-error { border-color: #F04138; }

    .rcp-ava {
        flex: 0 0 auto; width: 40px; height: 40px; border-radius: 11px;
        background: #fff; color: #1E1E1E; border: 1px solid #E5E7F5;
        display: flex; align-items: center; justify-content: center;
    }
    .rcp-ava svg { width: 22px; height: 22px; }

    .rcp-info { flex: 1 1 auto; min-width: 0; }
    .rcp-name { font-size: 14px; font-weight: 700; color: #22223A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rcp-name.is-empty { color: #9096AE; font-weight: 600; }
    .rcp-id { font-size: 12px; color: #9096AE; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .rcp-actions { flex: 0 0 auto; display: flex; align-items: center; gap: 4px; }
    .rcp-btn {
        width: 34px; height: 34px; border-radius: 9px; border: none; background: transparent;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        color: #A1A1CA; transition: background .15s, color .15s; padding: 0;
    }
    .rcp-btn svg { width: 19px; height: 19px; display: block; }
    .rcp-send:hover { background: #EEF0FE; color: #6972F0; }
    .rcp-del:hover { background: #FCEEEC; color: #F04138; }
    .rcp-btn:disabled { cursor: default; opacity: .6; }
    .rcp-spinner { animation: rcpSpin 1s linear infinite; }

    .rcp-add { display: flex; align-items: stretch; gap: 10px; }
    .rcp-add .rcp-input {
        flex: 1 1 0; min-width: 0; height: 42px !important; padding: 0 12px !important;
        border: 1px solid #D7D0EF !important; border-radius: 8px !important;
        font-family: inherit !important; font-size: 14px !important; box-sizing: border-box !important;
        background: #fff !important;
    }
    .rcp-add .rcp-input--id { flex: 0 0 40%; }
    .rcp-add-btn {
        flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px;
        height: 42px; padding: 0 18px; border-radius: 999px; border: none; cursor: pointer;
        background: #EAEDF8 !important; color: #4E4E61; font-weight: 700; font-size: 14px;
        font-family: inherit; transition: background .15s, color .15s;
    }
    .rcp-add-btn:hover { background: #DCE0F0 !important; color: #6972F0; }

    .rcp-hint { margin: 10px 0 0; font-size: 12px; color: #9096AE; }
</style>

<div class="rcp-wrap"
     x-data="{
        items: {{ json_encode($element->toValue() ?? []) }},
        draft: { chat_id: '', name: '' },
        name: '{{ $element->getColumn() }}',
        testUrl: '{{ route('chats.test') }}',
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
                this.items = [];
            }
        },
        addDraft() {
            const id = (this.draft.chat_id || '').trim();
            if (! id) {
                if (typeof Toast === 'object') Toast.error('Укажите ID чата'); else alert('Укажите ID чата');
                return;
            }
            this.items.push({ chat_id: id, name: (this.draft.name || '').trim(), status: 'default' });
            this.draft.chat_id = '';
            this.draft.name = '';
        },
        remove(index) {
            this.items.splice(index, 1);
        },
        rowClass(status) {
            if (status === 'success') return 'is-success';
            if (status === 'error') return 'is-error';
            return '';
        },
        async test(index) {
            const item = this.items[index];
            if (! item.chat_id) return;

            const serviceInput = document.querySelector(this.selectors.service);
            const tokenInput = document.querySelector(this.selectors.token);
            const apiUrlInput = document.querySelector(this.selectors.apiUrl);

            if (! serviceInput || ! serviceInput.value || ! tokenInput || ! tokenInput.value) {
                if (typeof Toast === 'object') Toast.error('Заполните Сервис и Токен'); else alert('Заполните Сервис и Токен');
                return;
            }

            item.status = 'loading';
            try {
                let result = await axios.get(this.testUrl, {
                    params: {
                        chat_id: item.chat_id,
                        service: serviceInput.value,
                        token: tokenInput.value,
                        api_url: apiUrlInput ? apiUrlInput.value : '',
                    }
                });
                item.status = result.data.success ? 'success' : 'error';
            } catch (error) {
                item.status = 'error';
            }
        }
    }"
>
    <div class="rcp-list">
        <template x-for="(item, index) in items" :key="index">
            <div class="rcp-item" :class="rowClass(item.status)">
                <input type="hidden" :name="`${name}[${index}][chat_id]`" :value="item.chat_id">
                <input type="hidden" :name="`${name}[${index}][name]`" :value="item.name">

                <span class="rcp-ava">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 1.25C12.5967 1.25 13.1689 1.48722 13.5908 1.90918C14.0128 2.33114 14.25 2.90326 14.25 3.5C14.25 4.09674 14.0128 4.66886 13.5908 5.09082C13.3515 5.33011 13.063 5.50727 12.75 5.61816V7.25H13C14.3927 7.25 15.513 7.24807 16.3916 7.36621C17.2916 7.48724 18.0497 7.74649 18.6514 8.34863L18.7598 8.46289C19.2851 9.04527 19.5203 9.76445 19.6338 10.6084C19.64 10.6548 19.6438 10.7022 19.6494 10.75C20.0399 10.7502 20.3798 10.7526 20.6631 10.7783C20.9922 10.8082 21.3179 10.8744 21.625 11.0518C21.9669 11.2492 22.2508 11.5331 22.4482 11.875C22.6256 12.1821 22.6918 12.5078 22.7217 12.8369C22.7506 13.1558 22.75 13.5463 22.75 14C22.75 14.4537 22.7506 14.8442 22.7217 15.1631C22.6918 15.4922 22.6256 15.8179 22.4482 16.125C22.2508 16.4669 21.9669 16.7508 21.625 16.9482C21.3179 17.1256 20.9922 17.1918 20.6631 17.2217C20.3798 17.2474 20.0399 17.2488 19.6494 17.249C19.6438 17.2971 19.6401 17.3449 19.6338 17.3916C19.5203 18.2356 19.2851 18.9547 18.7598 19.5371L18.6514 19.6514C18.0497 20.2535 17.2916 20.5128 16.3916 20.6338C15.513 20.7519 14.3927 20.75 13 20.75H12.4785C12.3387 20.9895 12.128 21.2783 11.8105 21.5605C11.0888 22.2021 9.90185 22.75 8 22.75C7.69727 22.75 7.4241 22.5675 7.30762 22.2881C7.19139 22.0088 7.25478 21.6866 7.46777 21.4717C7.46831 21.4711 7.46898 21.4705 7.46973 21.4697C7.47407 21.4652 7.48177 21.4566 7.49219 21.4453C7.51315 21.4227 7.54563 21.3871 7.58496 21.3408C7.66423 21.2475 7.76954 21.1141 7.87402 20.9561C7.93483 20.8641 7.98966 20.7707 8.03809 20.6787C7.02201 20.5898 6.1786 20.3767 5.51563 19.8066L5.34863 19.6514C4.74649 19.0497 4.48725 18.2916 4.36621 17.3916C4.35992 17.3448 4.35524 17.2972 4.34961 17.249C3.95958 17.2488 3.61997 17.2473 3.33691 17.2217C3.00779 17.1918 2.6821 17.1256 2.375 16.9482C2.03307 16.7508 1.74921 16.4669 1.55176 16.125C1.37438 15.8179 1.30818 15.4922 1.27832 15.1631C1.24941 14.8442 1.25 14.4537 1.25 14C1.25 13.5463 1.24941 13.1558 1.27832 12.8369C1.30818 12.5078 1.37438 12.1821 1.55176 11.875C1.74921 11.5331 2.03307 11.2492 2.375 11.0518C2.6821 10.8744 3.00779 10.8082 3.33691 10.7783C3.61997 10.7527 3.95958 10.7502 4.34961 10.75C4.35521 10.7022 4.35996 10.6549 4.36621 10.6084C4.48725 9.70843 4.74649 8.95026 5.34863 8.34863L5.46289 8.24023C6.04527 7.71489 6.76445 7.47971 7.6084 7.36621C8.48698 7.24807 9.60729 7.25 11 7.25H11.25V5.61816C10.937 5.50727 10.6485 5.33011 10.4092 5.09082C9.98722 4.66886 9.75 4.09674 9.75 3.5C9.75 2.90326 9.98722 2.33114 10.4092 1.90918C10.8311 1.48722 11.4033 1.25 12 1.25ZM11 8.75C9.565 8.75 8.56395 8.75197 7.80859 8.85352C7.16633 8.93989 6.78805 9.08828 6.51856 9.30957L6.40918 9.40918C6.13254 9.68551 5.95221 10.0747 5.85352 10.8086C5.75197 11.5639 5.75 12.565 5.75 14C5.75 15.435 5.75197 16.4361 5.85352 17.1914C5.95221 17.9253 6.13254 18.3145 6.40918 18.5908H6.41016C6.82028 19.0015 7.4581 19.186 9.02246 19.2334C9.42766 19.2457 9.75 19.578 9.75 19.9834C9.74993 20.3802 9.64399 20.755 9.50488 21.082C10.1576 20.9178 10.5648 20.6613 10.8145 20.4395C11.0224 20.2546 11.1425 20.076 11.208 19.9551C11.2408 19.8944 11.26 19.8477 11.2695 19.8223C11.2738 19.8108 11.2758 19.8037 11.2764 19.8018C11.2762 19.8023 11.2767 19.8034 11.2764 19.8047L11.2734 19.8135V19.8154C11.3579 19.483 11.6568 19.25 12 19.25H13C14.435 19.25 15.4361 19.248 16.1914 19.1465C16.9253 19.0478 17.3145 18.8675 17.5908 18.5908L17.6904 18.4814C17.9117 18.2119 18.0601 17.8337 18.1465 17.1914C18.248 16.4361 18.25 15.435 18.25 14C18.25 12.565 18.248 11.5639 18.1465 10.8086C18.0601 10.1663 17.9117 9.78805 17.6904 9.51855L17.5908 9.40918C17.3145 9.13254 16.9253 8.95221 16.1914 8.85352C15.4361 8.75197 14.435 8.75 13 8.75H11ZM13.5527 15.8975C13.884 15.6515 14.3528 15.7193 14.6006 16.0498C14.849 16.3812 14.7816 16.8521 14.4502 17.1006H14.4482L14.4453 17.1035C14.4435 17.1049 14.4407 17.1068 14.4385 17.1084C14.4342 17.1115 14.4294 17.1152 14.4238 17.1191C14.4122 17.1273 14.3971 17.1371 14.3799 17.1484C14.3454 17.1711 14.2993 17.1993 14.2422 17.2314C14.1279 17.2957 13.9686 17.3753 13.7637 17.4521C13.3519 17.6065 12.7624 17.75 12 17.75C11.2376 17.75 10.6481 17.6065 10.2363 17.4521C10.0314 17.3753 9.8721 17.2957 9.75781 17.2314C9.70068 17.1993 9.65463 17.1711 9.62012 17.1484C9.60285 17.1371 9.58779 17.1273 9.57617 17.1191C9.57059 17.1152 9.5658 17.1115 9.56152 17.1084C9.55932 17.1068 9.55654 17.1049 9.55469 17.1035L9.55176 17.1006H9.5498C9.21838 16.8521 9.15098 16.3812 9.39941 16.0498C9.647 15.7195 10.1151 15.6521 10.4463 15.8975C10.4548 15.9029 10.4701 15.9124 10.4922 15.9248C10.5445 15.9542 10.6355 15.9998 10.7637 16.0479C11.0187 16.1434 11.4296 16.25 12 16.25C12.5704 16.25 12.9813 16.1434 13.2363 16.0479C13.3645 15.9998 13.4555 15.9542 13.5078 15.9248C13.5293 15.9127 13.5441 15.903 13.5527 15.8975ZM4.26172 12.251C3.91191 12.2517 3.66672 12.2549 3.47266 12.2725C3.24762 12.2929 3.16582 12.327 3.125 12.3506C3.01103 12.4164 2.9164 12.511 2.85059 12.625C2.82701 12.6658 2.79286 12.7476 2.77246 12.9727C2.75112 13.2081 2.75 13.5187 2.75 14C2.75 14.4813 2.75112 14.7919 2.77246 15.0273C2.79286 15.2524 2.82701 15.3342 2.85059 15.375C2.9164 15.489 3.01103 15.5836 3.125 15.6494C3.16582 15.673 3.24762 15.7071 3.47266 15.7275C3.66672 15.7451 3.91191 15.7473 4.26172 15.748C4.25067 15.2207 4.25 14.6391 4.25 14C4.25 13.3605 4.25065 12.7785 4.26172 12.251ZM19.7373 12.251C19.7484 12.7785 19.75 13.3605 19.75 14C19.75 14.6391 19.7484 15.2207 19.7373 15.748C20.0876 15.7473 20.3331 15.7451 20.5273 15.7275C20.7524 15.7071 20.8342 15.673 20.875 15.6494C20.989 15.5836 21.0836 15.489 21.1494 15.375C21.173 15.3342 21.2071 15.2524 21.2275 15.0273C21.2489 14.7919 21.25 14.4813 21.25 14C21.25 13.5187 21.2489 13.2081 21.2275 12.9727C21.2071 12.7476 21.173 12.6658 21.1494 12.625C21.0836 12.511 20.989 12.4164 20.875 12.3506C20.8342 12.327 20.7524 12.2929 20.5273 12.2725C20.3331 12.2549 20.0876 12.2517 19.7373 12.251ZM9 11.25C9.41421 11.25 9.75 11.5858 9.75 12V13C9.75 13.4142 9.41421 13.75 9 13.75C8.58579 13.75 8.25 13.4142 8.25 13V12C8.25 11.5858 8.58579 11.25 9 11.25ZM15 11.25C15.4142 11.25 15.75 11.5858 15.75 12V13C15.75 13.4142 15.4142 13.75 15 13.75C14.5858 13.75 14.25 13.4142 14.25 13V12C14.25 11.5858 14.5858 11.25 15 11.25ZM12 2.75C11.8011 2.75 11.6104 2.82907 11.4697 2.96973C11.3291 3.11038 11.25 3.30109 11.25 3.5C11.25 3.69891 11.3291 3.88962 11.4697 4.03027C11.6104 4.17093 11.8011 4.25 12 4.25C12.1989 4.25 12.3896 4.17093 12.5303 4.03027C12.6709 3.88962 12.75 3.69891 12.75 3.5C12.75 3.30109 12.6709 3.11038 12.5303 2.96973C12.3896 2.82907 12.1989 2.75 12 2.75Z" fill="currentColor"/></svg>
                </span>

                <div class="rcp-info">
                    <div class="rcp-name" :class="! item.name ? 'is-empty' : ''" x-text="item.name || 'Без названия'"></div>
                    <div class="rcp-id">ID <span x-text="item.chat_id"></span></div>
                </div>

                <div class="rcp-actions">
                    <button type="button" class="rcp-btn rcp-send" @click="test(index)"
                            :disabled="! item.chat_id || item.status === 'loading'" title="Проверить">
                        <svg x-show="item.status !== 'loading'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 12 20 4l-6 16-3-7z"/>
                        </svg>
                        <svg x-show="item.status === 'loading'" class="rcp-spinner" viewBox="0 0 24 24" fill="none" style="display:none;">
                            <circle cx="12" cy="12" r="9" stroke="#E5E7F5" stroke-width="2.4"/>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="#6972F0" stroke-width="2.4" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <button type="button" class="rcp-btn rcp-del" @click="remove(index)" title="Удалить">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9M4.77 5.79a48 48 0 0 1 3.48-.4m7.5 0v-.91c0-1.18-.91-2.16-2.09-2.2a52 52 0 0 0-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0c-.6-.06-1.2-.11-1.8-.15m-5.7.15c.6-.06 1.2-.11 1.8-.15m0 0a48.1 48.1 0 0 1 3.9 0m-9.44.56L5.84 19.67A2.25 2.25 0 0 0 8.08 21.75h7.84a2.25 2.25 0 0 0 2.24-2.08L19.23 5.79"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div class="rcp-add">
        <input type="text" x-model="draft.chat_id" class="rcp-input rcp-input--id"
               placeholder="ID чата (-100...)" @keydown.enter.prevent="addDraft()">
        <input type="text" x-model="draft.name" class="rcp-input"
               placeholder="Название чата" @keydown.enter.prevent="addDraft()">
        <button type="button" class="rcp-add-btn" @click="addDraft()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Добавить
        </button>
    </div>

    <p class="rcp-hint">Кому бот шлёт уведомления — ID и название</p>
</div>
