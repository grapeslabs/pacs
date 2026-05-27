<style>
    .zones-preview-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 20px;
    }
    .zones-preview-player-container {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        max-height: 60vh;
        background: #000;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        border-radius: 8px;
    }
    .zones-preview-link {
        font-size: 14px;
        color: #6366f1;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-weight: 500;
        transition: opacity 0.2s;
    }
    .zones-preview-link:hover {
        opacity: 0.8;
    }
</style>

<div class="zones-preview-wrapper" data-show-when-field="{{ $element->getNameAttribute() }}" x-data="{
    zonesData: {{ json_encode($element->getValue() ?? []) }},
    colors: {
        rectangles: '{{ $colorRectangles }}',
        lines: '{{ $colorLines }}',
        polygons: '{{ $colorPolygons }}'
    },
    videoEl: null,
    vueApp: null,
    ctx: null,

    init() {
        this.ctx = this.$refs.canvas.getContext('2d');

        if (typeof this.zonesData === 'string') {
            try {
                this.zonesData = JSON.parse(this.zonesData);
            } catch (e) {
                this.zonesData = [];
            }
        }

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

        this.draw();
    },

    hexToRgba(color, alpha) {
        if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(color)) {
            let c = color.substring(1).split('');
            if (c.length === 3) {
                c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c = '0x' + c.join('');
            return 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',' + alpha + ')';
        }
        if (color.startsWith('rgba')) {
             return color.replace(/[\d\.]+\)$/g, alpha + ')');
        }
        if (color.startsWith('rgb')) {
             return color.replace('rgb', 'rgba').replace(')', ', ' + alpha + ')');
        }
        return color;
    },

    draw() {
        const width = this.$refs.canvas.width;
        const height = this.$refs.canvas.height;

        this.ctx.clearRect(0, 0, width, height);

        if (!Array.isArray(this.zonesData)) {
            return;
        }

        this.ctx.lineWidth = 2;

        this.zonesData.forEach(item => {
            if (!item.type || !Array.isArray(item.zones)) {
                return;
            }

            const colorStroke = this.colors[item.type] || '#ffffff';
            const colorFill = this.hexToRgba(colorStroke, 0.3);

            this.ctx.strokeStyle = colorStroke;
            this.ctx.fillStyle = colorFill;

            if (item.type === 'rectangles') {
                item.zones.forEach(zone => {
                    if (zone.x1 === undefined || zone.y1 === undefined || zone.x2 === undefined || zone.y2 === undefined) return;

                    const x = zone.x1 * width;
                    const y = zone.y1 * height;
                    const w = (zone.x2 - zone.x1) * width;
                    const h = (zone.y2 - zone.y1) * height;

                    this.ctx.strokeRect(x, y, w, h);
                    this.ctx.fillRect(x, y, w, h);
                });
            } else if (item.type === 'lines') {
                item.zones.forEach(zone => {
                    if (zone.x1 === undefined || zone.y1 === undefined || zone.x2 === undefined || zone.y2 === undefined) return;

                    this.ctx.beginPath();
                    this.ctx.moveTo(zone.x1 * width, zone.y1 * height);
                    this.ctx.lineTo(zone.x2 * width, zone.y2 * height);
                    this.ctx.stroke();
                });
            } else if (item.type === 'polygons') {
                item.zones.forEach(polygon => {
                    if (!Array.isArray(polygon) || polygon.length < 2) return;

                    this.ctx.beginPath();
                    this.ctx.moveTo(polygon[0].x1 * width, polygon[0].y1 * height);

                    for (let i = 1; i < polygon.length; i++) {
                        if (polygon[i].x1 !== undefined && polygon[i].y1 !== undefined) {
                            this.ctx.lineTo(polygon[i].x1 * width, polygon[i].y1 * height);
                        }
                    }

                    this.ctx.closePath();
                    this.ctx.stroke();
                    this.ctx.fill();
                });
            }
        });
    }
}" x-cloak>
    <div class="zones-preview-player-container" x-ref="playerContainer">
        <div
            x-ref="streamPlayer"
            style="width: 100%; height: 100%"
            data-item="{{ json_encode($item) }}"
        ></div>
        <canvas x-ref="canvas"
                style="position: absolute; z-index: 10; pointer-events: none;">
        </canvas>
    </div>

    @if($setupUrl)
        <div>
            <a href="{{ $setupUrl }}" class="zones-preview-link">
                Перейти к настройке &rarr;
            </a>
        </div>
    @endif
</div>
