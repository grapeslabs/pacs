<style>
    .zone-preview-wrapper {
        position: relative;
        width: 100%;
        background-color: #000;
        overflow: hidden;
        border-radius: 0.375rem;
        aspect-ratio: 16 / 9;
    }
    .zone-preview-player-container {
        width: 100%;
        height: 100%;
    }
    .zone-preview-rect {
        position: absolute;
        border: 2px dashed #ff0000;
        background: rgba(255, 0, 0, 0.1);
        pointer-events: none;
        z-index: 10;
        box-sizing: border-box;
    }
    .zone-preview-setup-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        color: #6366f1;
        font-size: 0.875rem;
        text-decoration: none;
    }
    .zone-preview-setup-link:hover {
        text-decoration: underline;
    }
</style>

@php
    $rawCoords = $element->toValue();
    $coords = is_string($rawCoords) ? json_decode($rawCoords, true) : $rawCoords;
    $coordsData = (is_array($coords) && isset($coords['x1'])) ? $coords : ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0];

    $data = $element->getData();
    $modelData = $data ? (is_object($data) && method_exists($data, 'toArray') ? $data->toArray() : (array)$data) : [];
    $uid = data_get($modelData, 'uid');
@endphp

<div
    x-data="{
        coords: @js($coordsData),
        modelData: @js($modelData),
        uid: @js($uid),
        player: null,
        isMounted: false,
        pollInterval: null,
        videoEl: null,
        nativeW: 0,
        nativeH: 0,
        content: null,
        get hasZone() {
            return this.coords.x2 > this.coords.x1 || this.coords.y2 > this.coords.y1;
        },
        calcContent() {
            if (!this.videoEl) return;
            const vw = this.videoEl.videoWidth;
            const vh = this.videoEl.videoHeight;
            if (vw > 0 && vh > 0) { this.nativeW = vw; this.nativeH = vh; }
            if (!this.nativeW || !this.nativeH) return;

            const wrapperRect = this.$refs.zoneWrapper.getBoundingClientRect();
            const videoRect = this.videoEl.getBoundingClientRect();
            const videoOffsetX = videoRect.left - wrapperRect.left;
            const videoOffsetY = videoRect.top - wrapperRect.top;
            const videoW = videoRect.width;
            const videoH = videoRect.height || 1;

            const fitScale = Math.min(videoW / this.nativeW, videoH / this.nativeH);
            const contentW = fitScale >= 1 ? this.nativeW : this.nativeW * fitScale;
            const contentH = fitScale >= 1 ? this.nativeH : this.nativeH * fitScale;

            this.content = {
                x: videoOffsetX + (videoW - contentW) / 2,
                y: videoOffsetY + (videoH - contentH) / 2,
                w: contentW,
                h: contentH,
            };
        },
        zoneStyle() {
            if (!this.content) {
                return {
                    left:   (this.coords.x1 * 100) + '%',
                    top:    (this.coords.y1 * 100) + '%',
                    width:  ((this.coords.x2 - this.coords.x1) * 100) + '%',
                    height: ((this.coords.y2 - this.coords.y1) * 100) + '%',
                };
            }
            const { x, y, w, h } = this.content;
            return {
                left:   (x + this.coords.x1 * w) + 'px',
                top:    (y + this.coords.y1 * h) + 'px',
                width:  ((this.coords.x2 - this.coords.x1) * w) + 'px',
                height: ((this.coords.y2 - this.coords.y1) * h) + 'px',
            };
        },
        init() {
            if (typeof window.mountVideoPlayer !== 'function') {
                Array.from(this.$refs.viteScripts.children).forEach(el => {
                    const tag = el.tagName.toLowerCase();
                    if (tag !== 'script' && tag !== 'link') return;
                    const isScript = tag === 'script';
                    const url = isScript ? el.src : el.href;
                    if (!url) return;
                    const alreadyLoaded = Array.from(document.head.querySelectorAll(tag))
                        .some(s => (isScript ? s.src : s.href) === url);
                    if (!alreadyLoaded) {
                        const newEl = document.createElement(tag);
                        Array.from(el.attributes).forEach(attr => newEl.setAttribute(attr.name, attr.value));
                        document.head.appendChild(newEl);
                    }
                });
            }

            const resizeObserver = new ResizeObserver(() => this.calcContent());
            resizeObserver.observe(this.$refs.zoneWrapper);
            window.addEventListener('resize', () => this.calcContent());

            this.pollInterval = setInterval(() => {
                const isVisible = this.$el.offsetParent !== null;

                if (isVisible && typeof window.mountVideoPlayer === 'function' && this.uid) {
                    if (!this.isMounted) {
                        this.$refs.playerContainer.dataset.item = JSON.stringify(this.modelData);
                        this.player = window.mountVideoPlayer(this.$refs.playerContainer);
                        this.isMounted = true;
                    }
                    const video = this.$refs.playerContainer.querySelector('video');
                    if (video && video !== this.videoEl) {
                        this.videoEl = video;
                        resizeObserver.observe(video);
                        video.addEventListener('loadedmetadata', () => this.calcContent());
                        this.calcContent();
                    }
                } else if (this.isMounted && !isVisible) {
                    if (this.player) {
                        this.player.unmount();
                        this.player = null;
                    }
                    this.$refs.playerContainer.innerHTML = '';
                    this.isMounted = false;
                    this.videoEl = null;
                    this.content = null;
                }
            }, 500);
        },
        destroy() {
            clearInterval(this.pollInterval);
            if (this.player) this.player.unmount();
        }
    }"
    class="zone-preview-container moonshine-field"
    x-cloak
>
    <div x-ref="viteScripts" style="display: none;">
        @vite(['resources/js/zone-preview.js'])
    </div>

    <input type="hidden"
           name="{{ $element->getNameAttribute() }}"
           :value="JSON.stringify(coords)">

    <div class="zone-preview-wrapper" x-ref="zoneWrapper">
        <div
            x-ref="playerContainer"
            class="zone-preview-player-container"
        ></div>

        <div
            x-show="hasZone"
            class="zone-preview-rect"
            :style="zoneStyle()"
        ></div>
    </div>

    @if($element->getSetupUrl())
        <a href="{{ $element->getSetupUrl() }}" class="zone-preview-setup-link">
            Перейти к настройке
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </a>
    @endif
</div>
