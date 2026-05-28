<style>
    .zone-editor-wrapper {
        position: relative;
        width: 100%;
        background: #1e1e2d;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }
    .zone-editor-player-container {
        position: relative;
        width: 100%;
        height: min(56.25vw, calc(100vh - 130px));
        background: #000;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    .zone-editor-controls {
        background: #222431;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #2b2b40;
    }
    .ze-btn {
        padding: 10px 24px;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s, opacity 0.2s;
        border: none;
        outline: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .ze-btn:hover {
        opacity: 0.9;
    }
    .ze-btn-freeze {
        background: #f3f4f6;
        color: #4f46e5;
    }
    .ze-btn-cancel {
        background: #ffffff;
        color: #4f46e5;
        text-decoration: none;
        margin-right: 12px;
    }
    .ze-btn-save {
        background: #6366f1;
        color: #ffffff;
    }
</style>

<div class="zone-editor-wrapper" x-data="{
    isDrawing: false,
    startX: 0,
    startY: 0,
    rect: null,
    normalizedRect: null,
    _nativeW: null,
    _nativeH: null,
    _content: null,
    isFrozen: false,
    videoEl: null,
    vueApp: null,
    ctx: null,
    initialZone: {{ json_encode($element->getValue() ?? null) }},

    init() {
        this.ctx = this.$refs.canvas.getContext('2d');
        const resizeObserver = new ResizeObserver(() => this.matchSize());
        resizeObserver.observe(this.$refs.playerContainer);
        window.addEventListener('resize', () => this.matchSize());

        let attempts = 0;
        const intervalId = setInterval(() => {
            attempts++;

            if (!this.vueApp && typeof window.mountVideoPlayer === 'function') {
                this.vueApp = window.mountVideoPlayer(this.$refs.streamPlayer);
            }

            const videoElement = this.$refs.streamPlayer?.querySelector('video');
            if (videoElement) {
                this.videoEl = videoElement;
                resizeObserver.observe(videoElement);
                videoElement.addEventListener('loadedmetadata', () => this.matchSize());
                clearInterval(intervalId);
                this.matchSize();
            }
            if (attempts > 40) {
                clearInterval(intervalId);
            }
        }, 500);
    },

    matchSize() {
        if (!this.videoEl) return;

        if (this.videoEl.videoWidth > 0 && this.videoEl.videoHeight > 0) {
            this._nativeW = this.videoEl.videoWidth;
            this._nativeH = this.videoEl.videoHeight;
        }
        if (!this._nativeW || !this._nativeH) return;

        const containerRect = this.$refs.playerContainer.getBoundingClientRect();
        const videoRect = this.videoEl.getBoundingClientRect();

        const cw = Math.round(containerRect.width);
        const ch = Math.round(containerRect.height);
        this.$refs.canvas.width = cw;
        this.$refs.canvas.height = ch;

        const videoOffsetX = videoRect.left - containerRect.left;
        const videoOffsetY = videoRect.top - containerRect.top;
        const videoW = videoRect.width;
        const videoH = videoRect.height || 1;

        // object-fit: scale-down — never upscales; uses native size when it fits, otherwise scales down
        const fitScale = Math.min(videoW / this._nativeW, videoH / this._nativeH);
        const contentW = fitScale >= 1 ? this._nativeW : this._nativeW * fitScale;
        const contentH = fitScale >= 1 ? this._nativeH : this._nativeH * fitScale;
        const contentX = videoOffsetX + (videoW - contentW) / 2;
        const contentY = videoOffsetY + (videoH - contentH) / 2;

        this._content = { x: contentX, y: contentY, w: contentW, h: contentH };

        if (this.initialZone && !this.normalizedRect) {
            const zone = typeof this.initialZone === 'string'
                ? JSON.parse(this.initialZone)
                : this.initialZone;
            if (zone?.x1 !== undefined) {
                this.normalizedRect = { x1: zone.x1, y1: zone.y1, x2: zone.x2, y2: zone.y2 };
            }
        }

        if (this.normalizedRect) {
            this.rect = {
                x: contentX + this.normalizedRect.x1 * contentW,
                y: contentY + this.normalizedRect.y1 * contentH,
                w: (this.normalizedRect.x2 - this.normalizedRect.x1) * contentW,
                h: (this.normalizedRect.y2 - this.normalizedRect.y1) * contentH,
            };
        }

        this.redraw();
    },

    startDrawing(e) {
        const bounding = this.$refs.canvas.getBoundingClientRect();
        this.startX = e.clientX - bounding.left;
        this.startY = e.clientY - bounding.top;
        this.isDrawing = true;
        this.rect = { x: this.startX, y: this.startY, w: 0, h: 0 };
    },

    draw(e) {
        if (!this.isDrawing) return;
        const bounding = this.$refs.canvas.getBoundingClientRect();
        this.rect.w = (e.clientX - bounding.left) - this.startX;
        this.rect.h = (e.clientY - bounding.top) - this.startY;
        this.redraw();
    },

    stopDrawing() {
        this.isDrawing = false;
        if (this.rect && this._content) {
            if (this.rect.w < 0) {
                this.rect.x += this.rect.w;
                this.rect.w = Math.abs(this.rect.w);
            }
            if (this.rect.h < 0) {
                this.rect.y += this.rect.h;
                this.rect.h = Math.abs(this.rect.h);
            }
            const { x, y, w, h } = this._content;
            this.normalizedRect = {
                x1: (this.rect.x - x) / w,
                y1: (this.rect.y - y) / h,
                x2: (this.rect.x + this.rect.w - x) / w,
                y2: (this.rect.y + this.rect.h - y) / h,
            };
        }
    },

    redraw() {
        this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
        if (this.rect) {
            this.ctx.strokeStyle = '#ef4444';
            this.ctx.lineWidth = 2;
            this.ctx.strokeRect(this.rect.x, this.rect.y, this.rect.w, this.rect.h);
            this.ctx.fillStyle = 'rgba(239, 68, 68, 0.2)';
            this.ctx.fillRect(this.rect.x, this.rect.y, this.rect.w, this.rect.h);
        }
    },

    toggleFreeze() {
        if (this.videoEl) {
            if (this.videoEl.paused) {
                this.videoEl.play();
                this.isFrozen = false;
            } else {
                this.videoEl.pause();
                this.isFrozen = true;
            }
        }
    },

    async save() {
        if (!this.rect || !this._content || !this.videoEl) return;

        const { x, y, w, h } = this._content;
        const payload = {
            x1: (this.rect.x - x) / w,
            y1: (this.rect.y - y) / h,
            x2: (this.rect.x + this.rect.w - x) / w,
            y2: (this.rect.y + this.rect.h - y) / h,
        };

        this.$refs.hiddenInput.value = JSON.stringify(payload);

        try {
            const form = this.$refs.hiddenInput.closest('form');
            const actionUrl = form ? form.action : window.location.href;

            const response = await axios.post(actionUrl, {
                ['{{ $element->getNameAttribute() }}']: payload,
                video_width: this.videoEl.videoWidth,
                video_height: this.videoEl.videoHeight,
            }, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const toastText = response.data?.message || '{{ $element->getSaveText() }}';
            const toastType = response.data?.messageType || 'success';
            const redirectUrl = response.data?.redirect || '{{ $element->getSaveUrl() }}';

            this.$dispatch('toast', { type: toastType, text: toastText });

            if (redirectUrl) {
                setTimeout(() => window.location.href = redirectUrl, 500);
            }
        } catch (error) {
            this.$dispatch('toast', { type: 'error', text: 'Ошибка сохранения' });
        }
    }
}">
    <div class="zone-editor-player-container" x-ref="playerContainer">
        <div
            x-ref="streamPlayer"
            style="width: 100%; height: 100%"
            data-item="{{ json_encode($item) }}"
        ></div>
        <canvas x-ref="canvas"
                style="position: absolute; left: 0; top: 0; z-index: 10; cursor: crosshair;"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing">
        </canvas>
    </div>

    <input type="hidden" name="{{ $element->getNameAttribute() }}" x-ref="hiddenInput">

    <div class="zone-editor-controls">
        <div>
            <button type="button"
                    @click="toggleFreeze"
                    class="ze-btn ze-btn-freeze"
                    x-text="isFrozen ? 'Разморозить кадр' : 'Заморозить кадр'">
            </button>
        </div>
        <div>
            @if($element->getCancelUrl())
                <a href="{{ $element->getCancelUrl() }}" class="ze-btn ze-btn-cancel">Отмена</a>
            @endif
            <button type="button" @click="save" class="ze-btn ze-btn-save">Сохранить</button>
        </div>
    </div>
</div>

@vite(['resources/js/zone-preview.js'])
