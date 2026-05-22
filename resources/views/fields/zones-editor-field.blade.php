<style>
    .zones-editor-wrapper {
        position: relative;
        width: 100%;
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }
    .zones-editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 16px;
    }
    .zones-editor-main-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }
    .zones-editor-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .zones-editor-toolbar-label {
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
    }
    .zones-editor-tools {
        display: flex;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
    }
    .zones-editor-tool-btn {
        background: transparent;
        border: none;
        padding: 6px 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        border-right: 1px solid #e5e7eb;
    }
    .zones-editor-tool-btn:last-child {
        border-right: none;
    }
    .zones-editor-tool-btn svg {
        width: 18px;
        height: 18px;
        stroke: #6b7280;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .zones-editor-tool-btn:hover {
        background: #f3f4f6;
    }
    .zones-editor-tool-btn.active {
        background: #7381F4;
    }
    .zones-editor-tool-btn.active svg {
        stroke: #ffffff;
    }
    .zones-editor-body {
        background: #1e1e2d;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .zones-editor-player-container {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        max-height: 70vh;
        background: #000;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    .zones-editor-controls {
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
        transition: opacity 0.2s;
        border: none;
        outline: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .ze-btn:hover {
        opacity: 0.85;
    }
    .ze-btn-ghost {
        background: #ffffff;
        color: #4f46e5;
    }
    .ze-btn-danger {
        background: #ef4444;
        color: #ffffff;
    }
    .ze-btn-save {
        gap: 10px;
        background: #6366f1;
        color: #ffffff;
    }
    .ze-controls-group {
        display: flex;
        gap: 12px;
    }
</style>

<div class="zones-editor-wrapper" x-data="{
    zonesData: {{ json_encode($element->getValue() ?? []) }},
    config: {{ json_encode($config) }},
    activeTool: '',
    isDrawing: false,
    startPos: null,
    currentPolygon: [],
    cursor: { x: null, y: null },
    isFrozen: false,
    videoEl: null,
    vueApp: null,
    ctx: null,

    init() {
        const formGroup = this.$el.closest('.form-group');
        if (formGroup) {
            const label = formGroup.querySelector('label');
            if (label) {
                label.style.setProperty('display', 'none', 'important');
            }
        }

        this.ctx = this.$refs.canvas.getContext('2d');

        if (typeof this.zonesData === 'string') {
            try {
                this.zonesData = JSON.parse(this.zonesData);
            } catch (e) {
                this.zonesData = [];
            }
        }
        if (!Array.isArray(this.zonesData)) {
            this.zonesData = [];
        }

        if (this.config.allowRectangles) this.activeTool = 'rectangles';
        else if (this.config.allowLines) this.activeTool = 'lines';
        else if (this.config.allowPolygons) this.activeTool = 'polygons';

        const resizeObserver = new ResizeObserver(() => this.matchSize());
        resizeObserver.observe(this.$refs.playerContainer);

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

        const drawLoop = () => {
            this.renderCanvas();
            requestAnimationFrame(drawLoop);
        };
        requestAnimationFrame(drawLoop);
    },

    setTool(tool) {
        if (!this.config.multiType && this.activeTool !== tool) {
            this.zonesData = [];
            this.currentPolygon = [];
            this.isDrawing = false;
        }
        this.activeTool = tool;
    },

    matchSize() {
        if (!this.videoEl || this.videoEl.videoWidth === 0) return;

        const containerRatio = this.videoEl.clientWidth / this.videoEl.clientHeight;
        const videoRatio = this.videoEl.videoWidth / this.videoEl.videoHeight;

        let actualWidth, actualHeight, offsetX = 0, offsetY = 0;

        if (containerRatio > videoRatio) {
            actualHeight = this.videoEl.clientHeight;
            actualWidth = actualHeight * videoRatio;
            offsetX = (this.videoEl.clientWidth - actualWidth) / 2;
        } else {
            actualWidth = this.videoEl.clientWidth;
            actualHeight = actualWidth / videoRatio;
            offsetY = (this.videoEl.clientHeight - actualHeight) / 2;
        }

        this.$refs.canvas.width = actualWidth;
        this.$refs.canvas.height = actualHeight;
        this.$refs.canvas.style.left = (this.videoEl.offsetLeft + offsetX) + 'px';
        this.$refs.canvas.style.top = (this.videoEl.offsetTop + offsetY) + 'px';
    },

    getRelativeCoords(e) {
        const bounding = this.$refs.canvas.getBoundingClientRect();
        return {
            x: Math.max(0, Math.min(1, (e.clientX - bounding.left) / this.$refs.canvas.width)),
            y: Math.max(0, Math.min(1, (e.clientY - bounding.top) / this.$refs.canvas.height))
        };
    },

    getAbsoluteCoords(e) {
        const bounding = this.$refs.canvas.getBoundingClientRect();
        return {
            x: e.clientX - bounding.left,
            y: e.clientY - bounding.top
        };
    },

    startDraw(e) {
        if (e.button === 2) return;
        const rel = this.getRelativeCoords(e);

        if (this.activeTool === 'polygons') {
            this.currentPolygon.push({ x1: rel.x, y1: rel.y });
            return;
        }

        this.startPos = rel;
        this.isDrawing = true;
    },

    moveDraw(e) {
        this.cursor = this.getAbsoluteCoords(e);
    },

    stopDraw(e) {
        if (this.activeTool === 'polygons' || !this.isDrawing) return;

        const endPos = this.getRelativeCoords(e);
        this.isDrawing = false;

        if (this.activeTool === 'rectangles') {
            const x1 = Math.min(this.startPos.x, endPos.x);
            const x2 = Math.max(this.startPos.x, endPos.x);
            const y1 = Math.min(this.startPos.y, endPos.y);
            const y2 = Math.max(this.startPos.y, endPos.y);

            if (Math.abs(x2 - x1) > 0.01 && Math.abs(y2 - y1) > 0.01) {
                this.addZone('rectangles', { x1, y1, x2, y2 });
            }
        } else if (this.activeTool === 'lines') {
            if (Math.abs(endPos.x - this.startPos.x) > 0.01 || Math.abs(endPos.y - this.startPos.y) > 0.01) {
                this.addZone('lines', { x1: this.startPos.x, y1: this.startPos.y, x2: endPos.x, y2: endPos.y });
            }
        }
    },

    finishPolygon(e) {
        if (this.activeTool === 'polygons' && this.currentPolygon.length > 2) {
            this.addZone('polygons', [...this.currentPolygon]);
            this.currentPolygon = [];
            this.isDrawing = false;
            this.cursor = { x: null, y: null };
        }
    },

    handleMouseLeave(e) {
        this.cursor = { x: null, y: null };
        this.isDrawing = false;

        if (this.activeTool === 'polygons' && this.currentPolygon.length > 2) {
            this.addZone('polygons', [...this.currentPolygon]);
        }
        if (this.activeTool === 'polygons') {
            this.currentPolygon = [];
        }
    },

    addZone(type, data) {
        if (!this.config.multiType) {
            let existingGroup = this.zonesData.find(z => z.type === type);
            if (existingGroup) {
                existingGroup.zones.push(data);
            } else {
                this.zonesData = [{ type: type, zones: [data] }];
            }
            return;
        }

        let typeGroup = this.zonesData.find(z => z.type === type);
        if (!typeGroup) {
            typeGroup = { type: type, zones: [] };
            this.zonesData.push(typeGroup);
        }
        typeGroup.zones.push(data);
    },

    clearAll() {
        this.zonesData = [];
        this.currentPolygon = [];
        this.isDrawing = false;
    },

    hexToRgba(color, alpha) {
        if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(color)) {
            let c = color.substring(1).split('');
            if (c.length === 3) c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            c = '0x' + c.join('');
            return 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',' + alpha + ')';
        }
        if (color.startsWith('rgba')) return color.replace(/[\d\.]+\)$/g, alpha + ')');
        if (color.startsWith('rgb')) return color.replace('rgb', 'rgba').replace(')', ', ' + alpha + ')');
        return color;
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

    renderCanvas() {
        if (!this.ctx || !this.$refs.canvas.width) return;

        const w = this.$refs.canvas.width;
        const h = this.$refs.canvas.height;

        this.ctx.clearRect(0, 0, w, h);

        this.ctx.lineWidth = 2;

        this.zonesData.forEach(group => {
            if (!group.type || !Array.isArray(group.zones)) return;

            const strokeColor = this.config.multiType
                ? (this.config.colors[group.type] || '#ef4444')
                : '#ef4444';
            this.ctx.strokeStyle = strokeColor;
            this.ctx.fillStyle = this.hexToRgba(strokeColor, 0.3);

            if (group.type === 'rectangles') {
                group.zones.forEach(z => {
                    const zx = z.x1 * w;
                    const zy = z.y1 * h;
                    const zw = (z.x2 - z.x1) * w;
                    const zh = (z.y2 - z.y1) * h;
                    this.ctx.strokeRect(zx, zy, zw, zh);
                    this.ctx.fillRect(zx, zy, zw, zh);
                });
            } else if (group.type === 'lines') {
                group.zones.forEach(z => {
                    this.ctx.beginPath();
                    this.ctx.moveTo(z.x1 * w, z.y1 * h);
                    this.ctx.lineTo(z.x2 * w, z.y2 * h);
                    this.ctx.stroke();
                });
            } else if (group.type === 'polygons') {
                group.zones.forEach(p => {
                    if (p.length < 2) return;
                    this.ctx.beginPath();
                    this.ctx.moveTo(p[0].x1 * w, p[0].y1 * h);
                    for (let i = 1; i < p.length; i++) {
                        this.ctx.lineTo(p[i].x1 * w, p[i].y1 * h);
                    }
                    this.ctx.closePath();
                    this.ctx.fill();
                    this.ctx.stroke();
                });
            }
        });

        const activeColor = this.config.multiType
            ? (this.config.colors[this.activeTool] || '#ef4444')
            : '#ef4444';
        this.ctx.strokeStyle = activeColor;
        this.ctx.fillStyle = this.hexToRgba(activeColor, 0.3);

        if (this.isDrawing && this.startPos && this.cursor.x !== null) {
            if (this.activeTool === 'rectangles') {
                const sx = this.startPos.x * w;
                const sy = this.startPos.y * h;
                const cw = this.cursor.x - sx;
                const ch = this.cursor.y - sy;
                this.ctx.strokeRect(sx, sy, cw, ch);
                this.ctx.fillRect(sx, sy, cw, ch);
            } else if (this.activeTool === 'lines') {
                this.ctx.beginPath();
                this.ctx.moveTo(this.startPos.x * w, this.startPos.y * h);
                this.ctx.lineTo(this.cursor.x, this.cursor.y);
                this.ctx.stroke();
            }
        }

        if (this.activeTool === 'polygons' && this.currentPolygon.length > 0) {
            this.ctx.beginPath();
            const startX = this.currentPolygon[0].x1 * w;
            const startY = this.currentPolygon[0].y1 * h;
            this.ctx.moveTo(startX, startY);

            for (let i = 1; i < this.currentPolygon.length; i++) {
                this.ctx.lineTo(this.currentPolygon[i].x1 * w, this.currentPolygon[i].y1 * h);
            }

            if (this.cursor.x !== null) {
                this.ctx.lineTo(this.cursor.x, this.cursor.y);
                this.ctx.lineTo(startX, startY);
            }

            this.ctx.stroke();
            this.ctx.fill();
        }
    },

    async save() {
        this.$refs.hiddenInput.value = JSON.stringify(this.zonesData);

        try {
            const form = this.$refs.hiddenInput.closest('form');
            const actionUrl = form ? form.action : window.location.href;

            const response = await axios.post(actionUrl, {
                ['{{ $element->getNameAttribute() }}']: this.zonesData,
                video_width: this.videoEl ? this.videoEl.videoWidth : 0,
                video_height: this.videoEl ? this.videoEl.videoHeight : 0,
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
            console.error(error);
            this.$dispatch('toast', { type: 'error', text: 'Ошибка сохранения' });
        }
    }
}" x-cloak>

    <div class="zones-editor-header">
        <div class="zones-editor-main-title">
            {{ $element->getLabel() }}
        </div>
        <div class="zones-editor-toolbar">
            <span class="zones-editor-toolbar-label">Режим разметки:</span>
            <div class="zones-editor-tools">
                <template x-if="config.allowRectangles">
                    <button type="button"
                            class="zones-editor-tool-btn"
                            :class="{ 'active': activeTool === 'rectangles' }"
                            @click="setTool('rectangles')">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </button>
                </template>
                <template x-if="config.allowLines">
                    <button type="button"
                            class="zones-editor-tool-btn"
                            :class="{ 'active': activeTool === 'lines' }"
                            @click="setTool('lines')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 21V3" stroke="#BDBCDB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </template>
                <template x-if="config.allowPolygons">
                    <button type="button"
                            class="zones-editor-tool-btn"
                            :class="{ 'active': activeTool === 'polygons' }"
                            @click="setTool('polygons')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.0745 5.02008C14.2039 4.68344 14.2495 4.32036 14.2072 3.96217C14.165 3.60398 14.0362 3.26146 13.8321 2.96416C13.6279 2.66687 13.3544 2.42373 13.0353 2.25576C12.7161 2.08778 12.3609 2 12.0003 2C11.6396 2 11.2844 2.08778 10.9653 2.25576C10.6461 2.42373 10.3727 2.66687 10.1685 2.96416C9.96432 3.26146 9.83556 3.60398 9.79332 3.96217C9.75109 4.32036 9.79665 4.68344 9.92609 5.02008L5.64993 8.07427C5.37864 7.84682 5.05694 7.68747 4.71162 7.60951C4.3663 7.53154 4.00735 7.53721 3.66467 7.62604C3.32198 7.71488 3.00548 7.8843 2.74151 8.12021C2.47754 8.35611 2.27374 8.65167 2.14709 8.98228C2.02044 9.31289 1.97459 9.66897 2.01338 10.0209C2.05216 10.3728 2.17445 10.7103 2.37006 11.0054C2.56567 11.3005 2.82895 11.5446 3.13796 11.7173C3.44697 11.8901 3.79277 11.9865 4.14657 11.9985L5.43548 17.7974C5.10476 17.966 4.82153 18.2147 4.61165 18.5209C4.40176 18.8271 4.27191 19.181 4.23396 19.5503C4.196 19.9196 4.25114 20.2925 4.39434 20.635C4.53755 20.9775 4.76425 21.2786 5.05376 21.5109C5.34328 21.7433 5.68637 21.8994 6.05172 21.965C6.41706 22.0306 6.79303 22.0037 7.14529 21.8866C7.49754 21.7696 7.81487 21.5661 8.06829 21.2949C8.32172 21.0236 8.50316 20.6932 8.59607 20.3338H15.4028C15.5389 20.8568 15.8608 21.3124 16.3084 21.6153C16.7559 21.9182 17.2985 22.0477 17.8346 21.9797C18.3707 21.9116 18.8637 21.6506 19.2213 21.2454C19.579 20.8402 19.7768 20.3187 19.7779 19.7782C19.778 19.3682 19.6647 18.9662 19.4505 18.6167C19.2364 18.2671 18.9298 17.9836 18.5645 17.7974L19.8534 11.9985C20.2072 11.9865 20.553 11.8901 20.862 11.7173C21.1711 11.5446 21.4343 11.3005 21.6299 11.0054C21.8255 10.7103 21.9478 10.3728 21.9866 10.0209C22.0254 9.66897 21.9796 9.31289 21.8529 8.98228C21.7263 8.65167 21.5225 8.35611 21.2585 8.12021C20.9945 7.8843 20.678 7.71488 20.3353 7.62604C19.9926 7.53721 19.6337 7.53154 19.2884 7.60951C18.9431 7.68747 18.6214 7.84682 18.3501 8.07427L14.0745 5.02008ZM12 5.33233C12.2947 5.33233 12.5773 5.21526 12.7857 5.00686C12.9941 4.79847 13.1111 4.51583 13.1111 4.22111C13.1111 3.9264 12.9941 3.64376 12.7857 3.43536C12.5773 3.22697 12.2947 3.10989 12 3.10989C11.7053 3.10989 11.4227 3.22697 11.2143 3.43536C11.0059 3.64376 10.8889 3.9264 10.8889 4.22111C10.8889 4.51583 11.0059 4.79847 11.2143 5.00686C11.4227 5.21526 11.7053 5.33233 12 5.33233ZM13.4278 5.92406C13.0282 6.26045 12.5223 6.44449 12 6.44355C11.4777 6.44449 10.9718 6.26045 10.5722 5.92406L6.2966 8.97825C6.49433 9.49126 6.49489 10.0594 6.29819 10.5728C6.10148 11.0862 5.72147 11.5084 5.23159 11.758L6.51994 17.5569C6.99951 17.5731 7.46095 17.7441 7.83528 18.0443C8.2096 18.3446 8.4767 18.7579 8.59663 19.2226H15.4034C15.5233 18.7579 15.7904 18.3446 16.1647 18.0443C16.539 17.7441 17.0005 17.5731 17.4801 17.5569L18.769 11.758C18.279 11.5085 17.8989 11.0863 17.702 10.5729C17.5052 10.0595 17.5057 9.49133 17.7034 8.97825L13.4278 5.92406ZM5.33326 9.77721C5.33326 10.0719 5.21619 10.3546 5.00781 10.563C4.79944 10.7714 4.51682 10.8884 4.22213 10.8884C3.92744 10.8884 3.64482 10.7714 3.43645 10.563C3.22807 10.3546 3.11101 10.0719 3.11101 9.77721C3.11101 9.4825 3.22807 9.19986 3.43645 8.99146C3.64482 8.78307 3.92744 8.66599 4.22213 8.66599C4.51682 8.66599 4.79944 8.78307 5.00781 8.99146C5.21619 9.19986 5.33326 9.4825 5.33326 9.77721ZM7.5555 19.7782C7.5555 20.0729 7.43844 20.3556 7.23006 20.564C7.02169 20.7723 6.73907 20.8894 6.44438 20.8894C6.14969 20.8894 5.86707 20.7723 5.6587 20.564C5.45032 20.3556 5.33326 20.0729 5.33326 19.7782C5.33326 19.4835 5.45032 19.2008 5.6587 18.9924C5.86707 18.784 6.14969 18.667 6.44438 18.667C6.73907 18.667 7.02169 18.784 7.23006 18.9924C7.43844 19.2008 7.5555 19.4835 7.5555 19.7782ZM19.7779 10.8884C20.0726 10.8884 20.3552 10.7714 20.5636 10.563C20.7719 10.3546 20.889 10.0719 20.889 9.77721C20.889 9.4825 20.7719 9.19986 20.5636 8.99146C20.3552 8.78307 20.0726 8.66599 19.7779 8.66599C19.4832 8.66599 19.2006 8.78307 18.9922 8.99146C18.7838 9.19986 18.6667 9.4825 18.6667 9.77721C18.6667 10.0719 18.7838 10.3546 18.9922 10.563C19.2006 10.7714 19.4832 10.8884 19.7779 10.8884ZM18.6667 19.7782C18.6667 20.0729 18.5497 20.3556 18.3413 20.564C18.1329 20.7723 17.8503 20.8894 17.5556 20.8894C17.2609 20.8894 16.9783 20.7723 16.7699 20.564C16.5616 20.3556 16.4445 20.0729 16.4445 19.7782C16.4445 19.4835 16.5616 19.2008 16.7699 18.9924C16.9783 18.784 17.2609 18.667 17.5556 18.667C17.8503 18.667 18.1329 18.784 18.3413 18.9924C18.5497 19.2008 18.6667 19.4835 18.6667 19.7782Z" fill="white"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <div class="zones-editor-body">
        <div class="zones-editor-player-container" x-ref="playerContainer" @mouseleave="handleMouseLeave">
            <div
                x-ref="streamPlayer"
                style="width: 100%; height: 100%"
                data-item="{{ json_encode($item) }}"
            ></div>

            <canvas x-ref="canvas"
                    style="position: absolute; z-index: 10; cursor: crosshair;"
                    @mousedown="startDraw"
                    @mousemove="moveDraw"
                    @mouseup="stopDraw"
                    @contextmenu.prevent="finishPolygon">
            </canvas>
        </div>

        <div class="zones-editor-controls">
            <div class="ze-controls-group">
                <button type="button"
                        @click="toggleFreeze"
                        class="ze-btn ze-btn-ghost"
                        x-text="isFrozen ? 'Возобновить' : 'Заморозить кадр'">
                </button>
                <button type="button"
                        @click="clearAll"
                        class="ze-btn ze-btn-danger">
                    Удалить всё
                </button>
            </div>
            <div class="ze-controls-group">
                @if($element->getCancelUrl())
                    <a href="{{ $element->getCancelUrl() }}" class="ze-btn ze-btn-ghost">Отмена</a>
                @endif
                <button type="button" @click="save" class="ze-btn ze-btn-save">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.541 17.5V13.5292C13.5408 13.355 13.5063 13.1826 13.4394 13.0219C13.3725 12.8611 13.2745 12.7151 13.1512 12.5922C13.0278 12.4693 12.8814 12.3719 12.7204 12.3056C12.5593 12.2393 12.3868 12.2054 12.2127 12.2058H7.78602C7.61187 12.2054 7.43935 12.2393 7.27832 12.3056C7.11729 12.3719 6.9709 12.4693 6.84753 12.5922C6.72416 12.7151 6.62623 12.8611 6.55934 13.0219C6.49244 13.1826 6.4579 13.355 6.45768 13.5292V17.5M13.541 2.73751V4.70584C13.5408 4.87998 13.5063 5.05237 13.4394 5.21316C13.3725 5.37394 13.2745 5.51996 13.1512 5.64286C13.0278 5.76577 12.8814 5.86315 12.7204 5.92943C12.5593 5.99572 12.3868 6.02961 12.2127 6.02918H7.78602C7.61187 6.02961 7.43935 5.99572 7.27832 5.92943C7.11729 5.86315 6.9709 5.76577 6.84753 5.64286C6.72416 5.51996 6.62623 5.37394 6.55934 5.21316C6.49244 5.05237 6.4579 4.87998 6.45768 4.70584V2.50001M6.45768 2.50001H5.57268C5.2244 2.49913 4.87936 2.56692 4.55729 2.69949C4.23523 2.83207 3.94246 3.02683 3.69572 3.27264C3.44898 3.51845 3.25312 3.81048 3.11933 4.13205C2.98554 4.45361 2.91645 4.79839 2.91602 5.14668V14.8525C2.91634 15.2009 2.98535 15.5457 3.11909 15.8674C3.25283 16.1891 3.44867 16.4812 3.69542 16.7271C3.94217 16.973 4.23498 17.1678 4.5571 17.3004C4.87922 17.4331 5.22433 17.5009 5.57268 17.5H14.4268C14.7752 17.5008 15.1203 17.4329 15.4424 17.3001C15.7644 17.1674 16.0572 16.9725 16.3039 16.7265C16.5505 16.4805 16.7463 16.1883 16.8799 15.8666C17.0136 15.5449 17.0825 15.2 17.0827 14.8517V7.12501C17.0825 6.77729 17.0136 6.43304 16.88 6.112C16.7465 5.79096 16.5508 5.49944 16.3043 5.25418L14.3193 3.27501C14.0918 3.04918 13.8277 2.86751 13.541 2.73751C13.1954 2.58099 12.8204 2.50003 12.441 2.50001H6.45768Z" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Сохранить настройки
                </button>
            </div>
        </div>
    </div>

    <input type="hidden" name="{{ $element->getNameAttribute() }}" x-ref="hiddenInput">
</div>

@vite(['resources/js/zone-preview.js'])
