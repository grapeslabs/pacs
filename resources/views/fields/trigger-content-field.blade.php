<div class="tc-panel"
     x-data="{
        mode: @js($mode),
        values: @js($values),
        template: @js($template),
        personOnly: @js(array_values($personOnly)),
        optionLabels: @js($options),
        eventType: @js($eventType),
        get personAvailable() { return this.eventType !== 'unknowed' && this.eventType !== 'crossing'; },
        available(key) {
            return this.personAvailable || this.personOnly.indexOf(key) === -1;
        },
        dropUnavailable() {
            Object.keys(this.optionLabels).forEach(key => {
                if (!this.available(key)) {
                    this.values[key] = false;
                    this.template = this.template.split('{' + this.optionLabels[key] + '}').join('');
                }
            });
            this.template = this.template.replace(/[ \t]{2,}/g, ' ');
        },
        insert(token) {
            const t = '{' + token + '}';
            const sep = (this.template && ! /\s$/.test(this.template)) ? ' ' : '';
            this.template = this.template + sep + t;
        },
        init() {
            const read = el => { if (el) this.eventType = el.value || ''; };
            const root = this.$root;
            const onChange = e => {
                if (! root.isConnected) {
                    document.removeEventListener('input', onChange, true);
                    document.removeEventListener('change', onChange, true);
                    return;
                }
                if (e.target && e.target.name === 'event_type_camera') {
                    read(e.target);
                    this.dropUnavailable();
                }
            };
            document.addEventListener('input', onChange, true);
            document.addEventListener('change', onChange, true);
            this.$nextTick(() => read(document.querySelector('[name=\'event_type_camera\']')));
        }
     }">

    <div class="tc-head">
        <div>
            <div class="tc-title">Содержимое уведомления</div>
            <div class="tc-sub" x-text="mode === 'template' ? 'Шаблон сообщения с плейсхолдерами' : 'Какие данные включить в сообщение'"></div>
        </div>
        <button type="button" class="tc-link" x-show="mode !== 'template'" @click="mode = 'template'">
            Открыть редактор <p>&lt;/&gt;</p>
        </button>
        <button type="button" class="tc-link" x-show="mode === 'template'" @click="mode = 'fields'" style="display:none;">
            Выйти из редактора
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
    </div>

    @foreach($options as $key => $label)
        <input type="hidden" name="{{ $column }}[{{ $key }}]" :value="values['{{ $key }}'] ? 1 : 0">
    @endforeach
    <input type="hidden" name="{{ $column }}[mode]" :value="mode">
    <input type="hidden" name="{{ $column }}[template]" :value="template">

    {{-- РЕЖИМ: чекбоксы --}}
    <div class="tc-grid" x-show="mode === 'fields'">
        @foreach($options as $key => $label)
            <label class="tc-check" x-show="available(@js($key))">
                <input type="checkbox" x-model="values['{{ $key }}']">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

    {{-- РЕЖИМ: шаблон --}}
    <div x-show="mode === 'template'" x-cloak style="display:none;">
        <div class="tc-chips">
            @foreach($options as $key => $label)
                <button type="button" class="tc-chip" x-show="available(@js($key))" @click="insert(@js($label))">+ {{ $label }}</button>
            @endforeach
        </div>

        <textarea class="tc-textarea" x-model="template" rows="4" placeholder="{Дата}, {Время}, {ФИО}…"></textarea>

        <div class="tc-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex:0 0 auto;margin-top:1px;"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 11v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="8" r="1" fill="currentColor"/></svg>
            <span>Слова в фигурных скобках — <b>плейсхолдеры</b>. Нажмите кнопку, чтобы вставить, например <code>{Фото из фотобанка}</code>, <code>{Комментарий}</code>, <code>{Дата}</code>, <code>{Время}</code> — при отправке они заменятся реальными значениями. Остальной текст отправляется как есть.</span>
        </div>
    </div>

    <style>
        .tc-panel { background:#F1F3FB; border-radius:16px; padding:16px 18px; }
        [x-cloak] { display:none !important; }
        .tc-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .tc-title { font-size:14px; font-weight:700; color:#22223A; }
        .tc-sub { font-size:12px; color:#9096AE; margin-top:2px; }
        .tc-link { display:inline-flex; align-items:center; gap:5px; background:none; border:none; cursor:pointer; color:#6972F0; font-weight:700; font-size:13px; font-family:inherit; white-space:nowrap; padding:0; }
        .tc-link:hover { color:#4E56D6; }

        .tc-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 20px; }
        .tc-check { display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px; color:#3B3B55; font-weight:600; }
        .tc-check input[type=checkbox] {
            -webkit-appearance:none; appearance:none; width:18px; height:18px; flex:0 0 auto; margin:0; cursor:pointer;
            border:1.5px solid #C7CCE0; border-radius:6px; background:#fff; position:relative;
            display:inline-flex; align-items:center; justify-content:center;
            transition:background .15s, border-color .15s;
        }
        .tc-check input[type=checkbox]:checked { background:#7E92F8; border-color:#7E92F8; color:#fff; }
        .tc-check input[type=checkbox]:checked::after {
            content:""; width:5px; height:9px; border:solid #fff; border-width:0 2px 2px 0;
            transform:rotate(45deg); margin-top:-2px;
        }

        .tc-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
        .tc-chip { display:inline-flex; align-items:center; background:#fff; border:1px solid #D7DBEF; color:#4E4E61; border-radius:8px; padding:7px 12px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; transition:background .15s,border-color .15s,color .15s; }
        .tc-chip:hover { background:#EEF0FE; border-color:#C8CDE8; color:#6972F0; }

        .tc-textarea { width:100%; min-height:100px; border:1px solid #D7D0EF !important; border-radius:10px !important; padding:12px 14px !important; font-family:inherit !important; font-size:14px !important; color:#22223A; background:#fff !important; box-sizing:border-box; resize:vertical; }

        .tc-info { display:flex; gap:8px; margin-top:12px; padding:12px 14px; background:#fff; border-radius:10px; font-size:12px; line-height:1.5; color:#6B7390; }
        .tc-info b { color:#4E4E61; }
        .tc-info code { background:#EEF0FE; color:#5560D8; border-radius:4px; padding:1px 5px; font-size:11.5px; }
    </style>
</div>
