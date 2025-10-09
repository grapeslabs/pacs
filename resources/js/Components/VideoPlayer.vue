<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import Hls from 'hls.js';

const props = defineProps({
    src: { type: String, default: '' },
    autoplay: { type: Boolean, default: true },
    muted: { type: Boolean, default: true },
    volume: { type: Number, default: 1 },
    playbackRate: { type: Number, default: 1 }
});

const emit = defineEmits(['error', 'ready', 'timeupdate', 'play', 'pause', 'durationchange']);

const videoElement = ref(null);
let hlsInstance = null;

const initHls = async () => {
    if (!videoElement.value || !props.src) return;

    destroyHls();
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    const canPlayHlsNative = videoElement.value.canPlayType('application/vnd.apple.mpegurl');

    if (isSafari && canPlayHlsNative) {
        videoElement.value.src = props.src;
        videoElement.value.load();
        if (props.autoplay) {
            videoElement.value.addEventListener('canplay', () => {
                videoElement.value.play().catch(e => console.warn('Autoplay failed', e));
            }, { once: true });
        }
    } else if (Hls.isSupported()) {
        hlsInstance = new Hls({
            debug: false,
            enableWorker: true,
            autoStartLoad: false,
            lowLatencyMode: false,
            maxBufferHole: 0.5,
            maxSeekHole: 2,
        });

        hlsInstance.attachMedia(videoElement.value);

        hlsInstance.on(Hls.Events.MEDIA_ATTACHED, () => {
            hlsInstance.loadSource(props.src);
        });

        hlsInstance.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
            emit('ready');

            let startPosition = 0;

            const level = data.levels[0];
            if (level && level.details && level.details.fragments) {
                const fragments = level.details.fragments;
                const firstValid = fragments.find(f => !f.gap);

                if (firstValid && firstValid.start > 0) {
                    startPosition = firstValid.start + 0.1;
                    console.log(`[HLS] Обнаружен стартовый GAP. Перенос старта на ${startPosition} сек.`);
                }
            }

            if (videoElement.value) {
                videoElement.value.currentTime = startPosition;
            }

            hlsInstance.startLoad(startPosition);

            if (props.autoplay) {
                const attemptPlay = () => {
                    videoElement.value?.play().catch(e => console.warn('Autoplay error', e));
                };

                if (videoElement.value.readyState >= 3) {
                    attemptPlay();
                } else {
                    videoElement.value.addEventListener('canplay', attemptPlay, { once: true });
                }
            }
        });

        hlsInstance.on(Hls.Events.ERROR, (event, data) => {
            if (data.details === Hls.ErrorDetails.BUFFER_STALLED_ERROR) {
                if (videoElement.value) {
                    videoElement.value.currentTime += 0.5;
                }
            }

            if (data.fatal) {
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        hlsInstance.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hlsInstance.recoverMediaError();
                        break;
                    default:
                        destroyHls();
                        emit('error', data);
                        break;
                }
            }
        });
    } else {
        emit('error', new Error('HLS не поддерживается в этом браузере'));
    }
};

const destroyHls = () => {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
    if (videoElement.value) {
        videoElement.value.removeAttribute('src');
        videoElement.value.load();
    }
};

// --- API ---
const play = () => videoElement.value?.play();
const pause = () => videoElement.value?.pause();
const seek = (time) => { if (videoElement.value && Number.isFinite(time)) videoElement.value.currentTime = time; };
const getCurrentTime = () => videoElement.value?.currentTime || 0;
const takeScreenshot = () => {
    if (!videoElement.value) return null;
    const canvas = document.createElement('canvas');
    canvas.width = videoElement.value.videoWidth;
    canvas.height = videoElement.value.videoHeight;
    canvas.getContext('2d').drawImage(videoElement.value, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg');
};

watch(() => props.src, (newVal) => {
    if (newVal) initHls();
    else destroyHls();
});
watch(() => props.volume, (v) => { if (videoElement.value) videoElement.value.volume = v; });
watch(() => props.playbackRate, (r) => { if (videoElement.value) videoElement.value.playbackRate = r; });

onMounted(() => {
    if (props.src) initHls();
});

onUnmounted(() => destroyHls());

const onTimeUpdate = () => emit('timeupdate', videoElement.value?.currentTime);

defineExpose({ play, pause, seek, getCurrentTime, takeScreenshot, videoElement });
</script>

<template>
    <video
        ref="videoElement"
        class="hls-video"
        crossorigin="anonymous"
        :muted="muted"
        @timeupdate="onTimeUpdate"
        @play="$emit('play')"
        @pause="$emit('pause')"
        @loadedmetadata="$emit('durationchange')"
    ></video>
</template>

<style scoped>
.hls-video { width: 100%; height: 100%; object-fit: scale-down; background: #000; }
</style>
