@php
    $isMultiple = $element->isMultiple();
    $column = $element->getColumn();
    $fileInputName = $column . ($isMultiple?'[]':'');
    $hiddenInputName = "hidden_$column" . ($isMultiple?'[]':'');
    $accept = $element->getAttribute('accept') ?? 'image/*';
@endphp

<style>
    .photo-field-wrapper {
        position: relative;
        width: 100%;
        font-family: inherit;
    }

    .photo-field-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    .photo-item {
        position: relative;
        width: 104px;
        height: 104px;
        border-radius: 24px;
        background: #f3f4f6;
        flex-shrink: 0;
    }

    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 24px;
    }

    .photo-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 24px;
        height: 24px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        z-index: 2;
    }

    .photo-star {
        position: absolute;
        bottom: -4px;
        left: -4px;
        width: 28px;
        height: 28px;
        background: #667eea;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        z-index: 2;
    }

    .photo-add {
        width: 104px;
        height: 104px;
        border-radius: 24px;
        border: 2px dashed #b5bacc;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #7c8299;
        background: transparent;
        transition: all 0.2s;
    }

    .photo-add:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }

    .photo-field-footer {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .photo-field-hint {
        color: #8e94a8;
        font-size: 14px;
        line-height: 1.4;
    }

    .photo-field-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 36px;
    }

    .photo-btn-more {
        background: #f0b32f;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .photo-field-counter {
        color: #8e94a8;
        font-size: 14px;
    }

    .photo-drop-overlay {
        position: absolute;
        inset: 0;
        background: #f1f3fe;
        border: 2px dashed #7381F4;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        z-index: 50;
        font-size: 15px;
        color: #7381F4;
        font-weight: 600;
        pointer-events: none;
        transition: background 0.15s;
    }
</style>

<div data-initial="{{ json_encode($photoItems) }}"
     x-data="(function() {
        return {
            items:[],
            isMultiple: {{ $isMultiple ? 'true' : 'false' }},
            maxLimit: {{ $limitCount }},
            dragOver: false,
            dragCounter: 0,
            expanded: false,

            get displayItems() {
                if (!this.isMultiple) return this.items.slice(0, 1);
                if (this.expanded || this.items.length <= 5) return this.items;
                return this.items.slice(0, 5);
            },

            get canAddMore() {
                if (!this.isMultiple && this.items.length >= 1) return false;
                return this.items.length < this.maxLimit;
            },

            generateId() {
                return Math.random().toString(36).substr(2, 9);
            },

            addFiles(files) {
                if (!this.isMultiple && files.length > 0) {
                    this.items =[];
                }

                let currentCount = this.items.length;
                for (let i = 0; i < files.length; i++) {
                    if (currentCount >= this.maxLimit) break;
                    let file = files[i];

                    if (!file.type.match('image.*')) continue;

                    let reader = new FileReader();
                    let id = this.generateId();

                    this.items.push({
                        id: id,
                        isOld: false,
                        file: file,
                        url: '',
                        path: ''
                    });

                    reader.onload = (e) => {
                        let targetItem = this.items.find(item => item.id === id);
                        if (targetItem) {
                            targetItem.url = e.target.result;
                        }
                    };
                    reader.readAsDataURL(file);
                    currentCount++;
                }
                this.syncInput();
            },

            handleDrop(e) {
                this.dragCounter = 0;
                this.dragOver = false;
                if (e.dataTransfer && e.dataTransfer.files) {
                    this.addFiles(e.dataTransfer.files);
                }
            },

            handlePaste(e) {
                if (!this.$el.contains(document.activeElement) && !this.$el.matches(':hover')) return;
                let items = (e.clipboardData || window.clipboardData).items;
                let files =[];
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        let originalFile = items[i].getAsFile();
                        if (originalFile) {
                            let ext = originalFile.type.split('/')[1] || 'png';
                            let newFileName = 'pasted_image_' + Date.now() + '_' + i + '.' + ext;
                            let renamedFile = new File([originalFile], newFileName, { type: originalFile.type });
                            files.push(renamedFile);
                        }
                    }
                }
                if (files.length > 0) {
                    this.addFiles(files);
                }
            },

            remove(id) {
                let idx = this.items.findIndex(i => i.id === id);
                if (idx !== -1) {
                    this.items.splice(idx, 1);
                    this.syncInput();
                }
            },

            handleFileSelect(e) {
                this.addFiles(e.target.files);
                e.target.value = '';
            },

            syncInput() {
                let dt = new DataTransfer();
                this.items.forEach(item => {
                    if (!item.isOld && item.file) {
                        dt.items.add(item.file);
                    }
                });
                this.$refs.fileInput.files = dt.files;
            },

            init() {
                let initialData = this.$el.dataset.initial;
                let initial = initialData ? JSON.parse(initialData) :[];

                if (Array.isArray(initial)) {
                    initial.forEach(img => {
                        this.items.push({
                            id: this.generateId(),
                            isOld: true,
                            file: null,
                            url: img.url,
                            path: img.path
                        });
                    });
                }

                let container = this.$el;
                let events =['dragenter', 'dragover', 'dragleave', 'drop'];

                let preventDefaults = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                };

                events.forEach(eventName => {
                    container.addEventListener(eventName, preventDefaults, false);
                });

                container.addEventListener('dragenter', () => {
                    this.dragCounter++;
                    this.dragOver = true;
                }, false);
                container.addEventListener('dragleave', () => {
                    this.dragCounter--;
                    if (this.dragCounter <= 0) {
                        this.dragCounter = 0;
                        this.dragOver = false;
                    }
                }, false);
                document.addEventListener('dragend', () => {
                    this.dragCounter = 0;
                    this.dragOver = false;
                });
                document.addEventListener('drop', () => {
                    this.dragCounter = 0;
                    this.dragOver = false;
                });
            }
        };
    })()"
     class="photo-field-wrapper"
     @drop.prevent="handleDrop"
     @paste.window="handlePaste">

    <div x-show="dragOver" class="photo-drop-overlay" style="display: none;">
        <svg width="48" height="48" viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M43.1604 11.25C26.3499 11.25 17.9447 11.25 12.7223 16.4703C7.5 21.6907 7.5 30.0926 7.5 46.8966C7.5 63.7004 7.5 72.1025 12.7223 77.3231C17.9447 82.5432 26.3499 82.5432 43.1604 82.5432C59.9707 82.5432 68.376 82.5432 73.5985 77.3231C78.8207 72.1025 78.8207 63.7004 78.8207 46.8966V45.0205" stroke="#7381F4" stroke-width="4" stroke-linecap="round"/>
            <path d="M18.75 78.7498C34.5374 60.9325 52.2795 37.303 78.75 55.0251" stroke="#7381F4" stroke-width="4"/>
            <path d="M67.4917 7.53125V37.5618M82.5127 22.4525L52.4707 22.5083" stroke="#7381F4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span style="margin-top: 4px;">Перетащите фотографии сюда</span>
    </div>

    <template x-for="item in items" :key="item.id">
        <template x-if="item.isOld">
            <input type="hidden" name="{{ $hiddenInputName }}" :value="item.path">
        </template>
    </template>

    <input type="file"
           name="{{ $fileInputName }}"
           x-ref="fileInput"
           class="hidden"
           style="display: none;"
        {{ $isMultiple ? 'multiple' : '' }}>

    <input type="file"
           x-ref="fileDialog"
           class="hidden"
           style="display: none;"
           {{ $isMultiple ? 'multiple' : '' }}
           @change="handleFileSelect"
           accept="{{ $accept }}">

    <div class="photo-field-grid">
        <template x-for="(item, index) in displayItems" :key="item.id">
            <div class="photo-item">
                <img :src="item.url" alt="">
                <button type="button" class="photo-remove" @click.stop="remove(item.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <template x-if="isMultiple && index === 0 && item === items[0] && !dragOver">
                    <div class="photo-star">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                             fill="currentColor">
                            <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="canAddMore">
            <button type="button" class="photo-add" @click="$refs.fileDialog.click()">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        </template>
    </div>

    <div class="photo-field-footer">
        <div class="photo-field-hint">
            Нажмите, перетащите или вставьте фото (Ctrl+V).
            @if($isMultiple)
                <br>Вы можете загрузить несколько фото одновременно.
            @endif
        </div>

        @if($isMultiple)
            <div class="photo-field-actions">
                <div>
                    <template x-if="items.length > 5 && !expanded">
                        <button type="button" class="photo-btn-more" @click="expanded = true">
                            + еще <span x-text="items.length - 5"></span>
                        </button>
                    </template>
                    <template x-if="items.length > 5 && expanded">
                        <button type="button" class="photo-btn-more" @click="expanded = false">
                            свернуть
                        </button>
                    </template>
                </div>
                <div class="photo-field-counter">
                    <template x-if="items.length < maxLimit">
                        <span x-text="items.length + '/' + maxLimit + ' фото добавлено'"></span>
                    </template>
                    <template x-if="items.length >= maxLimit">
                        <span>Достигнут лимит: <span x-text="maxLimit"></span> фото</span>
                    </template>
                </div>
            </div>
        @endif
    </div>
</div>
